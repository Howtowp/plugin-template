<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Eloquent\Relations;

use Forouzandea\Bluwp\Database\Eloquent\Model;

final class BelongsTo extends Relation
{
    public function __construct(Model $parent, string $related, string $foreignKey, string $localKey, private string $relationName = '')
    {
        parent::__construct($parent, $related, $foreignKey, $localKey);
    }
    public function query(): \Forouzandea\Bluwp\Database\Eloquent\Builder
    {
        if (!$this->applyParentConstraint) {
            return $this->query;
        }
        $key = $this->parent->getAttribute($this->foreignKey);
        return $this->constrainedQuery ??= ($key === null ? $this->relatedQuery()->whereIn($this->localKey, [])
            : $this->relatedQuery()->where($this->localKey, $key));
    }
    public function get(): \Forouzandea\Bluwp\Database\Eloquent\Collection
    {
        return $this->query()->get();
    }
    public function getResults(): ?Model
    {
        return $this->query()->first();
    }
    public function existenceQuery(string $parentTable): \Forouzandea\Bluwp\Database\Eloquent\Builder
    {
        $query = $this->relatedQuery();
        if ($query->getTableName() === $parentTable) {
            throw new \LogicException('Self-relation existence queries require aliases.');
        }
        return $query->whereColumn($query->getTableName() . '.' . $this->localKey, '=', $parentTable . '.' . $this->foreignKey);
    }
    public function associate(Model|int|string|null $model): Model
    {
        $this->parent->setAttribute($this->foreignKey, $model instanceof Model ? $model->getAttribute($this->localKey) : $model);
        $this->constrainedQuery = null;
        if ($this->relationName !== '') {
            if ($model instanceof Model || $model === null) {
                $this->parent->setRelation($this->relationName, $model);
            } else {
                $this->parent->unsetRelation($this->relationName);
            }
        }
        return $this->parent;
    }
    public function dissociate(): Model
    {
        return $this->associate(null);
    }
    public function eagerLoad(array $models, string $name): void
    {
        $keys = $this->keys($models, $this->foreignKey);
        $indexed = [];
        foreach (array_chunk($keys, 500) as $chunk) {
            foreach ($this->relatedQuery()->whereIn($this->localKey, $chunk)->get() as $related) {
                $indexed[(string) $related->getAttribute($this->localKey)] = $related;
            }
        }
        foreach ($models as $model) {
            $model->setRelation($name, $indexed[(string) $model->getAttribute($this->foreignKey)] ?? null);
        }
    }
}
