<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Http\Client;

use Forouzandea\Bluwp\Http\Client\Contracts\HttpClient;
use Forouzandea\Bluwp\Http\Client\Exceptions\HttpException;

final class RetryingHttpClient implements HttpClient
{
    private \Closure $sleep;
    private \Closure $clock;

    public function __construct(private HttpClient $client, private RetryPolicy $policy, ?callable $sleep = null, ?callable $clock = null)
    {
        $this->sleep = $sleep !== null
            ? \Closure::fromCallable($sleep)
            : static function (int $milliseconds): void {
                usleep($milliseconds * 1000);
            };
        $this->clock = $clock === null ? static fn (): float => hrtime(true) / 1000000 : \Closure::fromCallable($clock);
    }

    public function send(HttpRequest $request): HttpResponse
    {
        $maximumAttempts = $this->policy->permits($request) ? $this->policy->maximumAttempts : 1;
        $deadline = ($this->clock)() + $this->policy->maximumElapsedMilliseconds;
        $outcome = null;
        for ($attempt = 1; $attempt <= $maximumAttempts; ++$attempt) {
            $remaining = (int) floor($deadline - ($this->clock)());
            if ($remaining < 1000) {
                return $this->finish($outcome);
            }
            $attemptRequest = new HttpRequest(
                $request->method,
                $request->url,
                $request->body,
                $request->headers,
                min($request->timeout, intdiv($remaining, 1000)),
                $request->maximumResponseBytes,
                $request->maximumRedirects
            );
            $response = null;
            try {
                $response = $this->client->send($attemptRequest);
                $outcome = $response;
                if (!in_array($response->status, $this->policy->statuses, true)
                    || $attempt === $maximumAttempts) {
                    return $response;
                }
            } catch (HttpException $error) {
                $outcome = $error;
                if ($attempt === $maximumAttempts) {
                    throw $error;
                }
            }
            $delay = $this->policy->delayForAttempt($attempt, $response);
            if ($delay > $deadline - ($this->clock)() - 1000) {
                return $this->finish($outcome);
            }
            ($this->sleep)($delay);
        }

        throw new \LogicException(__('The HTTP retry loop ended unexpectedly.', BLUWP));
    }

    private function finish(HttpResponse|HttpException|null $outcome): HttpResponse
    {
        if ($outcome instanceof HttpResponse) {
            return $outcome;
        }
        throw $outcome ?? new HttpException(__('HTTP retry time budget exhausted.', BLUWP));
    }
}
