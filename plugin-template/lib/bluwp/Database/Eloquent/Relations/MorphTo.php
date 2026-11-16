<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database\Eloquent\Relations;

use Howtowp\Bluwp\Database\Eloquent\Model;
use Howtowp\Bluwp\Database\Eloquent\Builder;
use Howtowp\Bluwp\Database\Eloquent\Collection;

final class MorphTo extends Relation
{
    public function __construct(Model $parent, private string $type, string $id, private string $name)
    {
        $alias = $parent->getAttribute($type);
        $class = $alias === null ? $parent::class : Model::resolveMorphType((string) $alias);
        parent::__construct($parent, $class, $id, $parent->newRelatedInstance($class)->getKeyName());
    }
    public function query(): Builder
    {
        if (!$this->applyParentConstraint) {
            return $this->query;
        }
        return $this->constrainedQuery ??= ($this->parent->getAttribute($this->type) === null
            ? $this->relatedQuery()->whereIn($this->localKey, [])
            : $this->relatedQuery()->where($this->localKey, $this->parent->getAttribute($this->foreignKey)));
    }
    public function get(): Collection
    {
        return $this->query()->get();
    }
    public function getResults(): ?Model
    {
        return $this->query()->first();
    }
    public function associate(?Model $model): Model
    {
        $this->parent->setAttribute($this->type, $model?->getMorphClass());
        $this->parent->setAttribute($this->foreignKey, $model?->getKey());
        $this->parent->setRelation($this->name, $model);
        $this->constrainedQuery = null;
        if ($model) {
            $this->query = $this->query->forModel($model);
            $this->localKey = $model->getKeyName();
        }
        return $this->parent;
    }
    public function dissociate(): Model
    {
        return $this->associate(null);
    }
    public function existenceQuery(string $parentTable): Builder
    {
        throw new \LogicException('Use explicit typed queries for polymorphic existence checks.');
    }
    public function eagerLoad(array $models, string $name): void
    {
        $groups = [];
        foreach ($models as $model) {
            $alias = $model->getAttribute($this->type);
            $model->setRelation($name, null);
            if ($alias !== null) {
                $groups[(string) $alias][] = $model;
            }
        }
        foreach ($groups as $alias => $parents) {
            $target = $parents[0]->newRelatedInstance(Model::resolveMorphType($alias));
            $query = $this->query->forModel($target);
            $indexed = [];
            foreach (array_chunk($this->keys($parents, $this->foreignKey), 500) as $ids) {
                foreach ((clone $query)->whereIn($target->getKeyName(), $ids)->get() as $related) {
                    $indexed[(string) $related->getKey()] = $related;
                }
            }
            foreach ($parents as $parent) {
                $parent->setRelation($name, $indexed[(string) $parent->getAttribute($this->foreignKey)] ?? null);
            }
        }
    }
}
