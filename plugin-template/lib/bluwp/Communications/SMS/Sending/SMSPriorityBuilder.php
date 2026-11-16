<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\SMS\Sending;

use Howtowp\Bluwp\Communications\Enums\MessageStatus;
use Howtowp\Bluwp\Communications\SMS\Contracts\SMSRoutingPolicy;
use Howtowp\Bluwp\Communications\SMS\Data\SMSMessage;
use Howtowp\Bluwp\Communications\SMS\Data\SimpleSMSMessage;
use Howtowp\Bluwp\Communications\SMS\Data\PatternSMSMessage;
use Howtowp\Bluwp\Communications\SMS\Data\SMSPriority;
use Howtowp\Bluwp\Communications\SMS\Data\SMSPriorityResult;
use Howtowp\Bluwp\Communications\SMS\Data\SMSResult;
use Howtowp\Bluwp\Communications\SMS\Policies\SMSPriorityPolicy;

/** Request-local routing; no database, notification or application settings dependencies. */
final class SMSPriorityBuilder
{
    /** @var list<string> */
    private array $recipients = [];
    /** @var array<int, SMSPriority> */
    private array $priorities = [];
    /** @var list<string> */
    private array $defaults = [];
    private ?array $allowed = null;
    private string $template = '';
    private array $variables = [];
    private bool $pattern = false;
    private ?string $patternCode = null;
    private SMSRoutingPolicy $policy;
    private int $batchSize = SMSBatching::DEFAULT_SIZE;

    /**
     * @param \Closure(string): SMSBuilder $gateway
     * @param null|\Closure(SMSMessage, SMSResult): void $onResult
     */
    public function __construct(private \Closure $gateway, private ?\Closure $onResult = null, ?SMSRoutingPolicy $policy = null)
    {
        $this->policy = $policy ?? new SMSPriorityPolicy();
    }

    public function to(string|array $recipients): self
    {
        $this->recipients = SMSInput::recipients($recipients);
        return $this;
    }

    public function defaults(array $gateways): self
    {
        $this->assertGatewayNames($gateways);
        $this->defaults = array_values(array_unique(array_filter($gateways)));
        return $this;
    }

    public function batchSize(int $size): self
    {
        $this->batchSize = SMSBatching::validateSize($size);
        return $this;
    }

    public function only(?array $gateways): self
    {
        if ($gateways !== null) {
            $this->assertGatewayNames($gateways);
        }
        $this->allowed = $gateways;
        return $this;
    }

    public function usingPolicy(SMSRoutingPolicy $policy): self
    {
        $this->policy = $policy;
        return $this;
    }

    public function priority(int $rank, string $gateway, ?string $patternCode = null, ?string $message = null, ?array $variables = null): self
    {
        if (isset($this->priorities[$rank])) {
            throw new \InvalidArgumentException('SMS priority ranks must be unique.');
        }
        $this->priorities[$rank] = new SMSPriority($rank, $gateway, $patternCode, $message, $variables);
        return $this;
    }

    public function message(string $template, array $variables = []): self
    {
        $this->template = $template;
        $this->variables = $variables;
        $this->pattern = false;
        $this->patternCode = null;
        return $this;
    }

    public function pattern(string $template, array $variables = [], ?string $patternCode = null): self
    {
        $this->template = $template;
        $this->variables = $variables;
        $this->pattern = true;
        $this->patternCode = $patternCode;
        return $this;
    }

    /** @return list<SMSPriorityResult> */
    public function sendSimple(string $template, array $variables = []): array
    {
        return $this->message($template, $variables)->send();
    }

    /** @return list<SMSPriorityResult> */
    public function sendPattern(string $template, array $variables = [], ?string $patternCode = null): array
    {
        return $this->pattern($template, $variables, $patternCode)->send();
    }

    /** @return list<SMSPriorityResult> */
    public function send(?array $variables = null): array
    {
        $results = [];
        foreach ($this->sendBatches($variables) as $batch) {
            array_push($results, ...$batch);
        }
        return $results;
    }

    /** Each iteration sends one batch; routing and message state are captured now. @return \Generator<int, list<SMSPriorityResult>> */
    public function sendBatches(?array $variables = null): \Generator
    {
        if ($variables !== null) {
            $this->variables = $variables;
        }
        $snapshot = clone $this;
        if ($snapshot->recipients === []) {
            throw new \InvalidArgumentException(__('SMS recipients are required.', BLUWP));
        }
        $routes = $snapshot->routes();
        // Recipients are already validated. Preflight every route's shared content before any batch.
        foreach ($routes as $route) {
            $snapshot->prepare($snapshot->recipients[0], $route);
        }
        return $snapshot->batches($routes);
    }

    /** @param list<SMSPriority> $routes @return \Generator<int, list<SMSPriorityResult>> */
    private function batches(array $routes): \Generator
    {
        foreach (SMSBatching::recipients($this->recipients, $this->batchSize) as $recipients) {
            $results = [];
            foreach ($recipients as $recipient) {
                $results[] = $this->sendRecipient($recipient, $routes);
            }
            yield $results;
        }
    }

    /** @param list<SMSPriority> $routes */
    private function sendRecipient(string $recipient, array $routes): SMSPriorityResult
    {
        $attempts = [];
        $status = $routes === [] && $this->priorities === [] && $this->defaults === []
            ? MessageStatus::Unavailable : MessageStatus::Rejected;
        foreach ($routes as $route) {
            $message = $this->prepare($recipient, $route);
            try {
                $result = ($this->gateway)($route->gateway)->sendPrepared($message);
            } catch (\Throwable) {
                $result = SMSResult::failed($route->gateway, $recipient, 'gateway_exception', null, true);
            }
            if ($this->onResult !== null) {
                ($this->onResult)($message, $result);
            }
            $attempts[] = $result;
            $status = $result->statusEnum();
            if ($status !== MessageStatus::Rejected || !$this->policy->shouldContinue($status)) {
                break;
            }
        }
        return new SMSPriorityResult($recipient, $status, $attempts);
    }

    /** @return list<SMSPriority> */
    private function routes(): array
    {
        $routes = $this->priorities;
        if ($routes === []) {
            foreach ($this->defaults as $index => $gateway) {
                $routes[$index + 1] = new SMSPriority($index + 1, $gateway);
            }
        }
        ksort($routes, SORT_NUMERIC);
        $eligible = $this->policy->eligibleGateways(array_map(static fn (SMSPriority $route): string => $route->gateway, array_values($routes)), $this->allowed);
        // A policy can restrict the configured plan, never introduce an extra gateway.
        $allowed = $this->allowed;
        return array_values(array_filter($routes, static fn (SMSPriority $route): bool => in_array($route->gateway, $eligible, true)
            && ($allowed === null || in_array($route->gateway, $allowed, true))));
    }

    private function prepare(string $recipient, SMSPriority $route): SimpleSMSMessage|PatternSMSMessage
    {
        $template = $route->message ?? $this->template;
        $variables = $route->variables ?? $this->variables;
        if (!$this->pattern) {
            if ($route->patternCode !== null) {
                throw new \InvalidArgumentException('A pattern code requires pattern sending mode.');
            }
            return new SimpleSMSMessage($recipient, SMSInput::text($template, $variables));
        }
        [$code, $values] = SMSPatternTemplate::resolve($template, $variables, $route->patternCode ?? $this->patternCode);
        return new PatternSMSMessage($recipient, $code, $values);
    }

    private function assertGatewayNames(array $gateways): void
    {
        foreach ($gateways as $gateway) {
            if (!is_string($gateway) || ($gateway !== '' && !preg_match('/^[a-z][a-z0-9_]*$/D', $gateway))) {
                throw new \InvalidArgumentException('SMS gateway lists must contain valid gateway names.');
            }
        }
    }
}
