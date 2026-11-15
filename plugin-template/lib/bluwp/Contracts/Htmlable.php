<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Contracts;

interface Htmlable
{
    public function toHtml(): string;
}
