<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Support\Validation;

final class Attribute
{
    public function __construct(
        public readonly string $name,
        public readonly mixed $value,
        public readonly bool $present
    ) {
    }
}
