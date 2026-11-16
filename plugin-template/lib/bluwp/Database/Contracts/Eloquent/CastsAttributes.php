<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database\Contracts\Eloquent;

use Howtowp\Bluwp\Database\Eloquent\Model;

interface CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed;
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed;
}
