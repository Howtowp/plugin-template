<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Contracts\Eloquent;

use Forouzandea\Bluwp\Database\Eloquent\Model;

interface CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed;
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed;
}
