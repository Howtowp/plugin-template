<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\Client;

final class RetryPolicy
{
    private \Closure $randomInt;
    /**
     * @param int[] $statuses
     * @param null|callable(int, int): int $randomInt
     */
    public function __construct(
        public readonly int $maximumAttempts = 3,
        public readonly int $baseDelayMilliseconds = 250,
        public readonly array $statuses = [408, 429, 500, 502, 503, 504],
        public readonly bool $retryNonIdempotentRequests = false,
        public readonly bool $jitter = true,
        public readonly int $maximumElapsedMilliseconds = 45000,
        ?callable $randomInt = null
    ) {
        if ($maximumAttempts < 1 || $maximumAttempts > 10 || $baseDelayMilliseconds < 0 || $baseDelayMilliseconds > 60000
            || $maximumElapsedMilliseconds < 1000 || $maximumElapsedMilliseconds > 300000) {
            throw new \InvalidArgumentException(__('Invalid HTTP retry policy.', BLUWP));
        }
        $this->randomInt = $randomInt === null ? \Closure::fromCallable('random_int') : \Closure::fromCallable($randomInt);
        foreach ($statuses as $status) {
            if (!is_int($status) || $status < 100 || $status > 599) {
                throw new \InvalidArgumentException(__('Invalid retryable HTTP status.', BLUWP));
            }
        }
    }

    public function permits(HttpRequest $request): bool
    {
        return $request->method->isIdempotent() || $this->retryNonIdempotentRequests;
    }

    public function delayForAttempt(int $attempt, ?HttpResponse $response = null, ?int $now = null): int
    {
        $delay = $this->baseDelayMilliseconds;
        for ($step = 1; $step < $attempt && $delay > 0 && $delay < 60000; ++$step) {
            $delay = min(60000, $delay * 2);
        }
        if ($this->jitter) {
            $delay = ($this->randomInt)(0, $delay);
        }
        return max($delay, $this->retryAfterMilliseconds($response, $now ?? time()));
    }

    private function retryAfterMilliseconds(?HttpResponse $response, int $now): int
    {
        $value = $response?->header('Retry-After');
        if (!is_string($value)) {
            return 0;
        }
        $value = trim($value);
        if (preg_match('/^[0-9]+$/D', $value)) {
            $seconds = ltrim($value, '0');
            $limit = (string) intdiv(PHP_INT_MAX, 1000);
            return strlen($seconds) > strlen($limit) || (strlen($seconds) === strlen($limit) && strcmp($seconds, $limit) > 0)
                ? PHP_INT_MAX : (int) $seconds * 1000;
        }
        if (str_contains($value, "\0")) {
            return 0;
        }
        foreach (['D, d M Y H:i:s \\G\\M\\T', 'l, d-M-y H:i:s \\G\\M\\T', 'D M j H:i:s Y'] as $format) {
            // asctime uses two spaces before a single-digit day; normalize that spacing.
            $candidate = $format === 'D M j H:i:s Y' ? preg_replace('/ +/', ' ', $value) : $value;
            $wireValue = $candidate;
            $parseFormat = $format;
            if ($format === 'l, d-M-y H:i:s \\G\\M\\T'
                && preg_match('/^([A-Za-z]+, [0-9]{2}-[A-Za-z]{3}-)([0-9]{2})( [0-9]{2}:[0-9]{2}:[0-9]{2} GMT)$/D', $candidate, $parts)) {
                $year = intdiv((int) gmdate('Y', $now), 100) * 100 + (int) $parts[2];
                if ($year > (int) gmdate('Y', $now) + 50) {
                    $year -= 100;
                }
                $candidate = $parts[1] . $year . $parts[3];
                $parseFormat = 'l, d-M-Y H:i:s \\G\\M\\T';
            }
            $date = \DateTimeImmutable::createFromFormat('!' . $parseFormat, $candidate, new \DateTimeZone('UTC'));
            if ($date !== false && $date->format($format) === $wireValue) {
                if ($format === 'l, d-M-y H:i:s \\G\\M\\T' && $date > (new \DateTimeImmutable('@' . $now))->modify('+50 years')) {
                    return 0; // RFC 9110 interprets this obsolete two-digit year in the past century.
                }
                return max(0, $date->getTimestamp() - $now) * 1000;
            }
        }
        return 0;
    }
}
