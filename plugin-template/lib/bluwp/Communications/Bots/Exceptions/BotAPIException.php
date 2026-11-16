<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\Bots\Exceptions;

use Howtowp\Bluwp\Communications\Bots\Enums\BotErrorReason;

final class BotAPIException extends \RuntimeException
{
    public function __construct(private BotErrorReason $reason, string $message)
    {
        parent::__construct($message);
    }

    public function reason(): BotErrorReason
    {
        return $this->reason;
    }
}
