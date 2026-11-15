<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Eloquent;

use Forouzandea\Bluwp\Database\Query\Builder as QueryBuilder;
use Forouzandea\Bluwp\Database\Query\QueryState;
use Forouzandea\Bluwp\Database\Eloquent\Relations\Relation;

/** @phpstan-consistent-constructor */
class Builder extends QueryBuilder
{
    private array $disabledScopes = [];
    private array $with = [];
    private bool $disableAllScopes = false;

    public function __construct(private Model $model)
    {
        parent::__construct($model->getConnection(), $model->getTableName());
    }
    public function getModel(): Model
    {
        return $this->model;
    }
    public function forModel(Model $model): static
    {
        $query = clone $this;
        $query->model = $model;
        $query->connection = $model->getConnection();
        $query->table = $model->getTableName();
        return $query;
    }
    protected function defaultKeyName(): string
    {
        return $this->model->getKeyName();
    }
    private function relationQuery(string $path, ?callable $callback = null): Builder
    {
        [$name, $nested] = array_pad(explode('.', $path, 2), 2, null);
        if (!method_exists($this->model, $name)) {
            throw new \InvalidArgumentException('Unknown relationship.');
        }
        $relation = Relation::noConstraints(fn () => $this->model->$name());
        if (!$relation instanceof Relation) {
            throw new \LogicException('Invalid relationship method.');
        }
        $query = $relation->existenceQuery($this->table);
        if ($query->getConnection() !== $this->connection) {
            throw new \LogicException('Relationship existence queries require a shared connection.');
        }
        if ($nested !== null) {
            $query->whereHas($nested, $callback);
        } elseif ($callback) {
            $callback($query);
        }
        return $query;
    }
    public function has(string $relation, string $operator = '>=', int $count = 1, string $boolean = 'AND', ?callable $callback = null): static
    {
        $query = $this->relationQuery($relation, $callback);
        if ($operator === '>=' && $count === 1) {
            return $this->whereExists($query, $boolean);
        }
        if ($operator === '<' && $count === 1) {
            return $this->whereNotExists($query, $boolean);
        }
        return $this->whereSub($query->selectCount(), $operator, $count, $boolean);
    }
    public function whereHas(string $relation, ?callable $callback = null, string $operator = '>=', int $count = 1): static
    {
        return $this->has($relation, $operator, $count, 'AND', $callback);
    }
    public function doesntHave(string $relation, string $boolean = 'AND', ?callable $callback = null): static
    {
        return $this->has($relation, '<', 1, $boolean, $callback);
    }
    public function whereDoesntHave(string $relation, ?callable $callback = null): static
    {
        return $this->doesntHave($relation, 'AND', $callback);
    }
    public function withCount(string|array $relations): static
    {
        return $this->withRelationAggregate($relations, false);
    }
    public function withExists(string|array $relations): static
    {
        return $this->withRelationAggregate($relations, true);
    }
    private function withRelationAggregate(string|array $relations, bool $exists): static
    {
        foreach ((array) $relations as $key => $value) {
            $relation = is_int($key) ? $value : $key;
            $callback = is_int($key) ? null : $value;
            [$name, $alias] = array_pad(preg_split('/\\s+as\\s+/i', $relation), 2, null);
            $query = $this->relationQuery($name, $callback);
            $alias ??= str_replace('.', '_', $name) . ($exists ? '_exists' : '_count');
            $this->addSubquery($exists ? $query : $query->selectCount(), $alias, $exists);
        }
        return $this;
    }
    public function increment(string $column, int|float $amount = 1, array $extra = []): int
    {
        if ($this->model->timestamps) {
            $extra += [$this->model->getUpdatedAtColumn() => $this->model->freshTimestamp()];
        }
        return parent::increment($column, $amount, $extra);
    }
    protected function setExecutionState(QueryState $state): void
    {
        $this->state = $state;
        $this->disableAllScopes = true;
    }
    public function withoutGlobalScope(string $name): static
    {
        $this->disabledScopes[$name] = true;
        return $this;
    }
    public function withoutGlobalScopes(?array $scopes = null): static
    {
        if ($scopes === null) {
            $this->disableAllScopes = true;
        } else {
            foreach ($scopes as $scope) {
                $this->withoutGlobalScope($scope);
            }
        }
        return $this;
    }
    public function withTrashed(): static
    {
        return $this->withoutGlobalScope('softDeletes');
    }
    public function onlyTrashed(): static
    {
        if (!$this->model->usesSoftDeletes()) {
            throw new \LogicException('Model does not use soft deletes.');
        }
        return $this->withTrashed()->whereNotNull($this->table . '.' . $this->model->getDeletedAtColumn());
    }
    protected function executionState(): QueryState
    {
        $state = parent::executionState();
        if ($this->disableAllScopes) {
            return $state;
        }
        $scopes = $this->model->getGlobalScopes();
        foreach ($scopes as $name => $scope) {
            if (isset($this->disabledScopes[$name])) {
                continue;
            }
            $query = clone $this;
            $query->state = clone $state;
            $query->disableAllScopes = true;
            $original = $state->wheres;
            $query->state->wheres = [];
            $scope instanceof Scope ? $scope->apply($query, $this->model) : $scope($query, $this->model);
            $state = $query->state;
            $added = $state->wheres;
            $state->wheres = [];
            $state->appendWhereGroup($original);
            $state->appendWhereGroup($added);
        }
        return $state;
    }
    public function with(string|array $relations): static
    {
        foreach ((array) $relations as $key => $entry) {
            $name = is_int($key) ? $entry : $key;
            $constraint = is_int($key) ? null : $entry;
            if (!is_string($name) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)*$/D', $name)
                || ($constraint !== null && !is_callable($constraint))) {
                throw new \InvalidArgumentException('Invalid relation name or constraint.');
            }
            $this->with[$name] = $constraint;
        }
        return $this;
    }
    public function get(array|string $columns = ['*']): Collection
    {
        $models = new Collection(parent::get($columns)->map(fn (object $row): Model => $this->model->newFromBuilder((array) $row))->all());
        $this->eagerLoadRelations($models);
        return $models;
    }
    public function eagerLoadRelations(Collection $models): Collection
    {
        if (!$models->isEmpty()) {
            $groups = [];
            foreach ($this->with as $path => $constraint) {
                [$name, $nested] = array_pad(explode('.', $path, 2), 2, null);
                $groups[$name] ??= ['constraint' => null, 'nested' => []];
                if ($nested === null) {
                    $groups[$name]['constraint'] = $constraint;
                } else {
                    $groups[$name]['nested'][$nested] = $constraint;
                }
            }
            foreach ($groups as $name => $options) {
                if (!is_callable([$this->model, $name])) {
                    throw new \InvalidArgumentException('Unknown relation: ' . $name);
                }
                $relation = Relation::noConstraints(fn () => $this->model->$name());
                if (!$relation instanceof Relation) {
                    throw new \LogicException('Relation method must return a Relation.');
                }
                if ($options['constraint']) {
                    ($options['constraint'])($relation->getQuery());
                }
                if ($options['nested']) {
                    $relation->getQuery()->with($options['nested']);
                }
                $relation->eagerLoad($models->all(), $name);
            }
        }
        return $models;
    }
    public function whereGroup(callable $callback, string $boolean = 'AND'): static
    {
        $nested = new static($this->model);
        $callback($nested);
        if ($nested->state->wheres) {
            $this->state->wheres[] = ['type' => 'nested', 'wheres' => $nested->state->wheres, 'boolean' => $this->boolean($boolean)];
        }
        return $this;
    }
    public function find(mixed $id, array|string $columns = ['*']): Model|Collection|null
    {
        return is_array($id) ? $this->findMany($id, $columns) : (clone $this)->whereKey($id)->first($columns);
    }
    public function whereKey(mixed $id): static
    {
        return is_array($id) ? $this->whereIn($this->model->getKeyName(), $id) : $this->where($this->model->getKeyName(), $id);
    }
    public function findMany(array $ids, array|string $columns = ['*']): Collection
    {
        return (clone $this)->whereKey($ids)->get($columns);
    }
    public function firstOrFail(array|string $columns = ['*']): Model
    {
        return $this->first($columns) ?? throw (new ModelNotFoundException())->setModel($this->model::class);
    }
    public function findOrFail(mixed $id, array|string $columns = ['*']): Model|Collection
    {
        $result = $this->find($id, $columns);
        if ($result === null || (is_array($id) && $result->count() !== count(array_unique($id)))) {
            throw (new ModelNotFoundException())->setModel($this->model::class, $id);
        }
        return $result;
    }
    public function firstOrNew(array $attributes = [], array $values = []): Model
    {
        return (clone $this)->where($attributes)->first()
            ?? $this->newModelInstance(array_merge($attributes, $values));
    }
    public function firstOrCreate(array $attributes = [], array $values = []): Model
    {
        $model = (clone $this)->where($attributes)->first();
        if ($model !== null) {
            return $model;
        }
        try {
            return $this->create(array_merge($attributes, $values));
        } catch (\Forouzandea\Bluwp\Database\Exceptions\QueryException $error) {
            if (!$error->isUniqueConstraintViolation()) {
                throw $error;
            }
            return (clone $this)->where($attributes)->lockForUpdate()->first() ?? throw $error;
        }
    }
    public function __call(string $method, array $arguments): mixed
    {
        if ($this->model->hasNamedScope($method)) {
            $original = $this->state->wheres;
            $this->state->wheres = [];
            try {
                $result = $this->model->callNamedScope($method, array_merge([$this], $arguments));
                $added = $this->state->wheres;
            } finally {
                $this->state->wheres = $original;
            }
            $this->state->appendWhereGroup($added);
            return $result ?? $this;
        }
        throw new \BadMethodCallException('Unknown model query method: ' . $method);
    }
    public function paginate(?int $perPage = null, array|string $columns = ['*'], string $pageName = 'page', ?int $page = null, int|\Closure|null $total = null): \Forouzandea\Bluwp\Database\Pagination\LengthAwarePaginator
    {
        return parent::paginate($perPage ?? $this->model->getPerPage(), $columns, $pageName, $page, $total);
    }
    public function simplePaginate(?int $perPage = null, array|string $columns = ['*'], string $pageName = 'page', ?int $page = null): \Forouzandea\Bluwp\Database\Pagination\Paginator
    {
        return parent::simplePaginate($perPage ?? $this->model->getPerPage(), $columns, $pageName, $page);
    }
    public function cursorPaginate(?int $perPage = null, array|string $columns = ['*'], string $cursorName = 'cursor', \Forouzandea\Bluwp\Database\Pagination\Cursor|string|null $cursor = null): \Forouzandea\Bluwp\Database\Pagination\CursorPaginator
    {
        $query = clone $this;
        $key = $this->model->getKeyName();
        $state = $query->executionState();
        $query->setExecutionState($state);
        $orderedColumns = array_map(static fn ($order) => substr($order[0], strrpos('.' . $order[0], '.')), $state->orders);
        if (!in_array($key, $orderedColumns, true)) {
            $query->orderBy($this->table . '.' . $key);
        }
        return $query->cursorPage($perPage ?? $this->model->getPerPage(), $columns, $cursorName, $cursor);
    }
    private function cursorPage(int $perPage, array|string $columns, string $cursorName, \Forouzandea\Bluwp\Database\Pagination\Cursor|string|null $cursor): \Forouzandea\Bluwp\Database\Pagination\CursorPaginator
    {
        return parent::cursorPaginate($perPage, $columns, $cursorName, $cursor);
    }
    public function create(array $attributes): Model
    {
        $model = $this->newModelInstance($attributes);
        if (!$model->save()) {
            throw new \RuntimeException('Model creation was cancelled by an event.');
        }
        return $model;
    }
    private function newModelInstance(array $attributes = []): Model
    {
        return $this->model->newInstance($attributes)->useConnection($this->connection);
    }
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        if (!$attributes) {
            throw new \InvalidArgumentException('Lookup attributes are required.');
        }
        $model = $this->firstOrCreate($attributes, $values);
        if ($model->wasRecentlyCreated) {
            return $model;
        }
        if (!$model->update($values)) {
            throw new \RuntimeException('Model update was cancelled by an event.');
        }
        return $model;
    }
    public function update(array $data): int
    {
        if (!$data) {
            throw new \InvalidArgumentException('Update requires attributes.');
        }
        $values = $this->newModelInstance()->forceFill($data)->getAttributes();
        if ($this->model->timestamps && !array_key_exists($this->model->getUpdatedAtColumn(), $values)) {
            $values[$this->model->getUpdatedAtColumn()] = $this->model->freshTimestamp();
        }
        return parent::update($values);
    }
    public function delete(): int
    {
        if ($this->model->usesSoftDeletes()) {
            return $this->update([$this->model->getDeletedAtColumn() => $this->model->freshTimestamp()]);
        }
        return parent::delete();
    }
    public function upsert(array $rows, array|string $uniqueBy, ?array $updateColumns = null): int
    {
        if (!$rows) {
            return 0;
        }
        if (!is_array(reset($rows))) {
            $rows = [$rows];
        }
        $updateColumns ??= array_keys(reset($rows));
        $now = $this->model->freshTimestamp();
        $values = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                throw new \InvalidArgumentException('Upsert rows must be arrays.');
            }
            $model = $this->newModelInstance()->forceFill($row);
            if ($model->getKey() === null && method_exists($model, 'newUniqueId')) {
                $model->setAttribute($model->getKeyName(), $model->newUniqueId());
            }
            $data = $model->getAttributes();
            if ($model->timestamps) {
                $data += [$model->getCreatedAtColumn() => $now, $model->getUpdatedAtColumn() => $now];
            }
            $values[] = $data;
        }
        if ($this->model->timestamps) {
            $updateColumns[] = $this->model->getUpdatedAtColumn();
        }
        return parent::upsert($values, $uniqueBy, array_values(array_unique($updateColumns)));
    }
    public function forceDelete(): int
    {
        return parent::delete();
    }
    public function restore(): int
    {
        if (!$this->model->usesSoftDeletes()) {
            throw new \LogicException('Model does not use soft deletes.');
        }
        return (clone $this)->withTrashed()->update([$this->model->getDeletedAtColumn() => null]);
    }
}
