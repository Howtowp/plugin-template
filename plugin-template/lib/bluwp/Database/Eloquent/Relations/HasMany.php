<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database\Eloquent\Relations;

use Howtowp\Bluwp\Database\Eloquent\Collection;

class HasMany extends Relation
{
    public function query(): \Howtowp\Bluwp\Database\Eloquent\Builder
    {
        if (!$this->applyParentConstraint) {
            return $this->query;
        }
        $key = $this->parent->getAttribute($this->localKey);
        return $this->constrainedQuery ??= ($key === null ? $this->relatedQuery()->whereIn($this->foreignKey, [])
            : $this->relatedQuery()->where($this->foreignKey, $key));
    }
    public function get(): Collection
    {
        return $this->query()->get();
    }
    public function create(array $attributes = []): \Howtowp\Bluwp\Database\Eloquent\Model
    {
        $key = $this->parent->getAttribute($this->localKey);
        if ($key === null) {
            throw new \LogicException('Save the parent before creating a related model.');
        }
        $model = $this->query->getModel()->newInstance($attributes);
        $model->forceFill($this->foreignAttributes());
        if (!$model->save()) {
            throw new \RuntimeException('Model creation was cancelled by an event.');
        }
        return $model;
    }
    protected function foreignAttributes(): array
    {
        return [$this->foreignKey => $this->parent->getAttribute($this->localKey)];
    }
    public function eagerLoad(array $models, string $name): void
    {
        $keys = $this->keys($models, $this->localKey);
        $grouped = [];
        foreach (array_chunk($keys, 500) as $chunk) {
            foreach ($this->relatedQuery()->whereIn($this->foreignKey, $chunk)->get() as $related) {
                $grouped[(string) $related->getAttribute($this->foreignKey)][] = $related;
            }
        }
        foreach ($models as $model) {
            $model->setRelation($name, new Collection($grouped[(string) $model->getAttribute($this->localKey)] ?? []));
        }
    }
}
