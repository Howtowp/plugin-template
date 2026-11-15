<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\UI\Contracts;

use Forouzandea\Bluwp\Contracts\Arrayable;

interface Page extends Arrayable
{
    public function id(): string;
    public function title(): string;
    public function capability(): string;
    public function canView(): bool;
}
