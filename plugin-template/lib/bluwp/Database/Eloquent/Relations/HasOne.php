<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database\Eloquent\Relations;

use Howtowp\Bluwp\Database\Eloquent\Model;

final class HasOne extends HasMany
{
    public function getResults(): ?Model
    {
        return $this->query()->first();
    }
    public function eagerLoad(array $models, string $name): void
    {
        parent::eagerLoad($models, $name);
        foreach ($models as $model) {
            $model->setRelation($name, $model->getRelation($name)->first());
        }
    }
}
