<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS\Data;

use Forouzandea\Bluwp\Communications\Enums\MessageStatus;

final class SMSPriorityResult
{
    public readonly string $status;

    /** @param list<SMSResult> $attempts */
    public function __construct(public readonly string $recipient, MessageStatus $status, public readonly array $attempts)
    {
        $this->status = $status->value;
    }

    public function statusEnum(): MessageStatus
    {
        return MessageStatus::from($this->status);
    }
}
