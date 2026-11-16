<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation\Rules;

final class Max extends SizeRule
{
    public function name(): string
    {
        return 'max';
    } protected function passes(int|float $size): bool
    {
        return $size <= $this->limit;
    }
}
