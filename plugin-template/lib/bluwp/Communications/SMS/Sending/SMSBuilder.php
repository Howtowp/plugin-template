<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS\Sending;

use Forouzandea\Bluwp\Communications\SMS\Data\SMSMessage;
use Forouzandea\Bluwp\Communications\SMS\Data\SimpleSMSMessage;
use Forouzandea\Bluwp\Communications\SMS\Data\PatternSMSMessage;
use Forouzandea\Bluwp\Communications\SMS\Data\SMSResult;
use Forouzandea\Bluwp\Communications\SMS\Contracts\SMSGateway;
use Forouzandea\Bluwp\Communications\SMS\Contracts\SimpleSMSGateway;
use Forouzandea\Bluwp\Communications\SMS\Contracts\PatternSMSGateway;
use Forouzandea\Bluwp\Communications\SMS\Contracts\SMSGatewayCredit;

/** A request-scoped builder. The manager never stores active credentials or message state. */
final class SMSBuilder
{
    private array $recipients = [];
    private string $text = '';
    private ?string $template = null;
    private array $variables = [];
    private int $batchSize = SMSBatching::DEFAULT_SIZE;

    /** @param null|\Closure(SMSMessage, SMSResult): void $onResult */
    public function __construct(private SMSGateway|SimpleSMSGateway|PatternSMSGateway|SMSGatewayCredit $gateway, private ?\Closure $onResult = null)
    {
    }

    public function credit(): float
    {
        if (!$this->gateway instanceof SMSGatewayCredit) {
            throw new \LogicException('This SMS gateway does not support credit queries.');
        }
        return $this->gateway->credit();
    }

    public function to(string|array $recipients): self
    {
        $this->recipients = SMSInput::recipients($recipients);
        return $this;
    }

    public function message(string $text, array $data = []): self
    {
        $compiled = SMSInput::text($text, $data);
        $this->text = $compiled;
        $this->template = null;
        $this->variables = [];
        return $this;
    }

    public function batchSize(int $size): self
    {
        $this->batchSize = SMSBatching::validateSize($size);
        return $this;
    }

    public function pattern(string $template, array $variables = [], ?string $patternCode = null): self
    {
        [$this->template, $this->variables] = SMSPatternTemplate::resolve($template, $variables, $patternCode);
        $this->text = '';
        return $this;
    }

    /** @return list<SMSResult> */
    public function sendSimple(string $text, array $data = []): array
    {
        return $this->message($text, $data)->send();
    }

    /** @return list<SMSResult> */
    public function sendPattern(string $template, array $variables = [], ?string $patternCode = null): array
    {
        return $this->pattern($template, $variables, $patternCode)->send();
    }

    /** @return list<SMSResult> */
    public function send(): array
    {
        $results = [];
        foreach ($this->sendBatches() as $batch) {
            array_push($results, ...$batch);
        }
        return $results;
    }

    /** Each iteration sends one batch; obtaining the iterator sends nothing. @return \Generator<int, list<SMSResult>> */
    public function sendBatches(): \Generator
    {
        $snapshot = clone $this;
        if ($snapshot->recipients === []) {
            throw new \InvalidArgumentException(__('SMS recipients are required.', BLUWP));
        }
        // to() validated the entire recipient list; content and capabilities are shared by all batches.
        $snapshot->operationFor($snapshot->prepare($snapshot->recipients[0]));
        return $snapshot->batches();
    }

    /** @return \Generator<int, list<SMSResult>> */
    private function batches(): \Generator
    {
        foreach (SMSBatching::recipients($this->recipients, $this->batchSize) as $recipients) {
            $results = [];
            foreach ($recipients as $recipient) {
                $message = $this->prepare($recipient);
                $results[] = $this->executePrepared($message, $this->operationFor($message));
            }
            yield $results;
        }
    }

    private function prepare(string $recipient): SimpleSMSMessage|PatternSMSMessage
    {
        return $this->template === null ? new SimpleSMSMessage($recipient, $this->text)
            : new PatternSMSMessage($recipient, $this->template, $this->variables);
    }

    /** @internal Executes an already validated immutable message, without rendering it twice. */
    public function sendPrepared(SimpleSMSMessage|PatternSMSMessage $message): SMSResult
    {
        return $this->executePrepared($message, $this->operationFor($message));
    }

    /** @param \Closure(): SMSResult $operation */
    private function executePrepared(SMSMessage $message, \Closure $operation): SMSResult
    {
        $result = $operation();
        if ($this->onResult !== null) {
            ($this->onResult)($message, $result);
        }
        return $result;
    }

    /** @return \Closure(): SMSResult */
    private function operationFor(SimpleSMSMessage|PatternSMSMessage $message): \Closure
    {
        $gateway = $this->gateway;
        if ($message instanceof SimpleSMSMessage && $gateway instanceof SimpleSMSGateway) {
            return static fn (): SMSResult => $gateway->sendSimple($message);
        }
        if ($message instanceof PatternSMSMessage && $gateway instanceof PatternSMSGateway) {
            return static fn (): SMSResult => $gateway->sendPattern($message);
        }
        if ($gateway instanceof SMSGateway) {
            return static fn (): SMSResult => $gateway->send($message);
        }
        throw new \LogicException('This SMS gateway does not support the requested sending mode.');
    }

}
