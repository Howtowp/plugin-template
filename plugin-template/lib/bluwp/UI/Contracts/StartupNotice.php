<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\UI\Contracts;

interface StartupNotice
{
    /** @return array{type: string, messages: list<string>}|null */
    public function notice(): ?array;
}
