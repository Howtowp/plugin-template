<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS\Data;

use Forouzandea\Bluwp\Communications\Enums\MessageStatus;
use Forouzandea\Bluwp\Communications\SMS\Policies\SMSFailurePolicy;

/** Acceptance by a gateway is not a delivery receipt. */
final class SMSResult
{
    public readonly string $status;
    private function __construct(
        public readonly string $gateway,
        public readonly string $recipient,
        MessageStatus $status,
        public readonly ?string $messageId,
        public readonly ?string $errorCode,
        public readonly ?int $httpStatus,
        public readonly ?string $errorMessage,
        public readonly ?array $requestData = null,
        public readonly ?array $responseData = null
    ) {
        $this->status = $status->value;
    }

    public static function accepted(string $gateway, string $recipient, string $id, int $httpStatus): self
    {
        return new self($gateway, $recipient, MessageStatus::Accepted, $id, null, $httpStatus, null);
    }

    public static function failed(string $gateway, string $recipient, string $code, ?int $httpStatus = null, bool $uncertain = false, ?string $message = null): self
    {
        return new self($gateway, $recipient, $uncertain ? MessageStatus::Unknown : MessageStatus::Rejected, null, $code, $httpStatus, $message);
    }

    public function failureType(): ?string
    {
        return $this->failureTypeEnum()?->value;
    }

    public function statusEnum(): MessageStatus
    {
        return MessageStatus::from($this->status);
    }

    public function failureTypeEnum(): ?SMSFailureType
    {
        return SMSFailurePolicy::classify($this->gateway, $this->statusEnum(), $this->errorCode, $this->httpStatus);
    }

    /** Attach filtered HTTP diagnostics, preserving the send outcome. */
    public function withData(?array $requestData, ?array $responseData): self
    {
        return new self(
            $this->gateway,
            $this->recipient,
            $this->statusEnum(),
            $this->messageId,
            $this->errorCode,
            $this->httpStatus,
            $this->errorMessage,
            $requestData,
            $responseData
        );
    }
}
