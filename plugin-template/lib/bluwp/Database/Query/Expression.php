<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Query;

final class Expression
{
    public function __construct(public readonly string $sql, public readonly array $bindings = [])
    {
    }
}
