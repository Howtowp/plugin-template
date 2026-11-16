<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\SMS\Policies;

use Howtowp\Bluwp\Communications\Enums\MessageStatus;
use Howtowp\Bluwp\Communications\SMS\Contracts\SMSRoutingPolicy;

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
