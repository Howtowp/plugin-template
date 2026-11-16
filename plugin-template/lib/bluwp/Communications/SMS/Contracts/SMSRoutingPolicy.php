<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\SMS\Contracts;

use Howtowp\Bluwp\Communications\Enums\MessageStatus;

interface SMSRoutingPolicy
{
    /** @return list<string> */
    public function eligibleGateways(array $configured, ?array $allowed): array;

    /** Consulted only after a definite rejection. */
    public function shouldContinue(MessageStatus $status): bool;
}
