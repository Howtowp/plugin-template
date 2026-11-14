<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Eloquent\Relations;

use Forouzandea\Bluwp\Database\Eloquent\Model;
use Forouzandea\Bluwp\Database\Eloquent\Builder;
use Forouzandea\Bluwp\Database\Eloquent\Collection;
use Forouzandea\Bluwp\Database\Query\Builder as QueryBuilder;

final class BelongsToMany extends Relation
{
    private string $pivotTable;
    private array $pivotColumns = [];
    public function __construct(Model $parent, string $related, string $table, string $foreignPivotKey, private string $relatedPivotKey, string $parentKey, private string $relatedKey)
    {
        parent::__construct($parent, $related, $foreignPivotKey, $parentKey);
        if ($this->query->getConnection() !== $parent->getConnection()) {
            throw new \LogicException('Pivot joins require the same connection.');
        }
        $this->pivotTable = $parent->getTablePrefix() . $table;
        $target = $this->query->getTableName();
        $this->query->join($this->pivotTable, $target . '.' . $relatedKey, '=', $this->pivotTable . '.' . $relatedPivotKey)->select($target . '.*');
    }
    public function withPivot(string ...$columns): static
    {
        foreach ($columns as $column) {
            \Forouzandea\Bluwp\Database\Query\SqlCompiler::identifier($column);
        }
        $this->pivotColumns = array_values(array_unique(array_merge($this->pivotColumns, $columns)));
        return $this;
    }
    public function query(): Builder
    {
        if (!$this->applyParentConstraint) {
            return $this->query;
        }
        $key = $this->parent->getAttribute($this->localKey);
        return $this->constrainedQuery ??= ($key === null ? $this->relatedQuery()->whereIn($this->pivotTable . '.' . $this->foreignKey, [])
            : $this->relatedQuery()->where($this->pivotTable . '.' . $this->foreignKey, $key));
    }
    private function results(Builder $query): Collection
    {
        $columns = array_unique(array_merge([$this->foreignKey, $this->relatedPivotKey], $this->pivotColumns));
        foreach ($columns as $column) {
            $query->addSelect($this->pivotTable . '.' . $column . ' AS bluwp_pivot_' . $column);
        }
        $models = $query->get();
        foreach ($models as $model) {
            $pivot = [];
            foreach ($columns as $column) {
                $alias = 'bluwp_pivot_' . $column;
                $pivot[$column] = $model->getAttribute($alias);
                unset($model[$alias]);
            }
            $model->setRelation('pivot', new Pivot($pivot));
        }
        return $models;
    }
    public function get(): Collection
    {
        return $this->results(clone $this->query());
    }
    public function first(): ?Model
    {
        return $this->results((clone $this->query())->limit(1))->first();
    }
    public function eagerLoad(array $models, string $name): void
    {
        $grouped = [];
        foreach (array_chunk($this->keys($models, $this->localKey), 500) as $keys) {
            foreach ($this->results($this->relatedQuery()->whereIn($this->pivotTable . '.' . $this->foreignKey, $keys)) as $related) {
                $grouped[(string) $related->pivot->{$this->foreignKey}][] = $related;
            }
        }
        foreach ($models as $model) {
            $model->setRelation($name, new Collection($grouped[(string) $model->getAttribute($this->localKey)] ?? []));
        }
    }
    public function existenceQuery(string $parentTable): Builder
    {
        return $this->relatedQuery()->whereColumn($this->pivotTable . '.' . $this->foreignKey, '=', $parentTable . '.' . $this->localKey);
    }
    private function pivotQuery(): QueryBuilder
    {
        $key = $this->parent->getAttribute($this->localKey);
        if (!$this->parent->exists || $key === null) {
            throw new \LogicException('Save the parent before modifying a pivot.');
        }
        return (new QueryBuilder($this->parent->getConnection(), $this->pivotTable))->where($this->foreignKey, $key);
    }
    private function records(mixed $ids): array
    {
        if ($ids instanceof Collection) {
            $ids = $ids->modelKeys();
        }
        if ($ids instanceof Model) {
            $ids = [$ids->getAttribute($this->relatedKey)];
        }
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $result = [];
        foreach ($ids as $key => $value) {
            $id = is_array($value) ? $key : $value;
            if (!is_int($id) && !is_string($id)) {
                throw new \InvalidArgumentException('Pivot IDs must be strings or integers.');
            }
            $result[$id] = is_array($value) ? $value : [];
        }
        return $result;
    }
    public function attach(mixed $ids, array $attributes = []): void
    {
        $query = $this->pivotQuery();
        $this->parent->getConnection()->transaction(function () use ($ids, $attributes, $query): void {
            foreach ($this->records($ids) as $id => $extra) {
                $query->insert(array_merge($attributes, $extra, [$this->foreignKey => $this->parent->getAttribute($this->localKey), $this->relatedPivotKey => $id]));
            }
        });
    }
    public function detach(mixed $ids = null): int
    {
        $query = $this->pivotQuery();
        if ($ids !== null) {
            $query->whereIn($this->relatedPivotKey, array_keys($this->records($ids)));
        }
        return $query->delete();
    }
    public function updateExistingPivot(int|string $id, array $attributes): int
    {
        unset($attributes[$this->foreignKey], $attributes[$this->relatedPivotKey]);
        return $attributes ? $this->pivotQuery()->where($this->relatedPivotKey, $id)->update($attributes) : 0;
    }
    public function sync(mixed $ids, bool $detaching = true): array
    {
        $records = $this->records($ids);
        $query = $this->pivotQuery();
        return $this->parent->getConnection()->transaction(function () use ($records, $query, $detaching): array {
            $this->parent->newModelQuery()->where($this->localKey, $this->parent->getAttribute($this->localKey))->lockForUpdate()->firstOrFail();
            $current = $query->pluck($this->relatedPivotKey)->all();
            $removed = $detaching ? array_values(array_diff($current, array_keys($records))) : [];
            if ($removed) {
                $this->detach($removed);
            }
            $result = ['attached' => [], 'detached' => $removed, 'updated' => []];
            foreach ($records as $id => $attributes) {
                if (!in_array($id, $current)) {
                    $this->attach([$id => $attributes]);
                    $result['attached'][] = $id;
                } elseif ($attributes && $this->updateExistingPivot($id, $attributes) > 0) {
                    $result['updated'][] = $id;
                }
            }
            return $result;
        });
    }
}
