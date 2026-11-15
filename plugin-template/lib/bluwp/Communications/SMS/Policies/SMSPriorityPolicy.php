<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS\Policies;

use Forouzandea\Bluwp\Communications\Enums\MessageStatus;
use Forouzandea\Bluwp\Communications\SMS\Contracts\SMSRoutingPolicy;

final class SMSPriorityPolicy implements SMSRoutingPolicy
{
    public function eligibleGateways(array $configured, ?array $allowed): array
    {
        return $allowed === null ? array_values($configured) : array_values(array_intersect($configured, $allowed));
    }

    public function shouldContinue(MessageStatus $status): bool
    {
        return $status === MessageStatus::Rejected;
    }
}
