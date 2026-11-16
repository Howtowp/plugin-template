<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation\Rules;

final class Min extends SizeRule
{
    public function name(): string
    {
        return 'min';
    } protected function passes(int|float $size): bool
    {
        return $size >= $this->limit;
    }
}
