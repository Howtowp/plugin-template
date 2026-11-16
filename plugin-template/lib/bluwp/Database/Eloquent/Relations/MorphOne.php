<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database\Eloquent\Relations;

final class MorphOne extends MorphMany
{
    public function getResults(): ?\Howtowp\Bluwp\Database\Eloquent\Model
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
