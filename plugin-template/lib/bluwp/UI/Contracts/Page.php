<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\UI\Contracts;

use Howtowp\Bluwp\Contracts\Arrayable;

interface Page extends Arrayable
{
    public function id(): string;
    public function title(): string;
    public function capability(): string;
    public function canView(): bool;
}
