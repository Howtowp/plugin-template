<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS\Contracts;

use Forouzandea\Bluwp\Communications\Enums\MessageStatus;

interface SMSRoutingPolicy
{
    /** @return list<string> */
    public function eligibleGateways(array $configured, ?array $allowed): array;

    /** Consulted only after a definite rejection. */
    public function shouldContinue(MessageStatus $status): bool;
}
