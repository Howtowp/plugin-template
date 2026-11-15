<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Eloquent;

interface Scope
{
    public function apply(Builder $builder, Model $model): void;
}
