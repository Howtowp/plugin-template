<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation\Contracts;

interface MessageProvider
{
    public function message(string $rule): string;
}
