<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\Bots\Contracts;

interface MembershipContacts
{
    public function exists(string $botType, string $userId): bool;

    /** Return false when the number belongs to a different account in this bot. */
    public function save(string $botType, string $userId, string $number): bool;
}
