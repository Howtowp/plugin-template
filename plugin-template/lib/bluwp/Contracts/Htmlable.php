<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Contracts;

interface Htmlable
{
    public function toHtml(): string;
}
