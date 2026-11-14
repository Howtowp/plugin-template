<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Admin\Contracts;

interface NoticePolicy
{
    /** @param list<string> $hooks Owned WordPress admin screen suffixes. */
    public function register(array $hooks): void;
    public function suppress(): void;
}
