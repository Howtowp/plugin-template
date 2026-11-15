<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Query;

use Forouzandea\Bluwp\Database\Connection\ConnectionInterface;
use Forouzandea\Bluwp\Support\Collection;
use Forouzandea\Bluwp\Database\Pagination\LengthAwarePaginator;
use Forouzandea\Bluwp\Database\Pagination\Paginator;
use Forouzandea\Bluwp\Database\Pagination\Cursor;
use Forouzandea\Bluwp\Database\Pagination\CursorPaginator;
use Forouzandea\Bluwp\Database\Exceptions\ModelNotFoundException;

class Builder
{
    protected QueryState $state;
    protected SqlCompiler $compiler;
    protected bool $allowUnconstrained = true;

    public function __construct(protected ConnectionInterface $connection, protected string $table)
    {
        SqlCompiler::identifier($table);
        $this->state = new QueryState();
        $this->compiler = new SqlCompiler();
    }

    public function __clone()
    {
        $this->state = clone $this->state;
    }
    public function getTableName(): string
    {
        return $this->table;
    }
    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }
    public function hasLimitOrOffset(): bool
    {
        $state = $this->executionState();
        return $state->limit !== null || $state->offset !== null;
    }

    protected function boolean(string $boolean): string
    {
        $boolean = strtoupper($boolean);
        if (!in_array($boolean, ['AND', 'OR'], true)) {
            throw new \InvalidArgumentException('Invalid boolean.');
        }
        return $boolean;
    }

    protected function operator(string $operator): string
    {
        $operator = strtoupper(trim($operator));
        if (!in_array($operator, ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE'], true)) {
            throw new \InvalidArgumentException('Unsupported SQL operator.');
        }
        return $operator;
    }

    public function where(string|array|\Closure $column, mixed $operator = null, mixed $value = null, string $boolean = 'AND'): static
    {
        $boolean = $this->boolean($boolean);
        if (is_array($column)) {
            return $this->whereGroup(static function ($query) use ($column): void {
                foreach ($column as $key => $entry) {
                    is_int($key) && is_array($entry) ? $query->where(...$entry) : $query->where($key, $entry);
                }
            }, $boolean);
        }
        if ($column instanceof \Closure) {
            return $this->whereGroup($column, $boolean);
        }
        SqlCompiler::identifier($column);
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        $operator = $this->operator($operator ?? '=');
        if ($value === null) {
            if (!in_array($operator, ['=', '!=', '<>'], true)) {
                throw new \InvalidArgumentException('NULL supports only equality comparisons.');
            }
            return $this->whereNull($column, $boolean, $operator !== '=');
        }
        SqlCompiler::placeholder($value);
        $this->state->wheres[] = compact('column', 'operator', 'value', 'boolean') + ['type' => 'basic'];
        return $this;
    }

    public function orWhere(string|array|\Closure $column, mixed $operator = null, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->where($column, $operator, $value, 'OR');
    }

    public function whereGroup(callable $callback, string $boolean = 'AND'): static
    {
        $nested = new Builder($this->connection, $this->table);
        $callback($nested);
        if ($nested->state->wheres) {
            $this->state->wheres[] = ['type' => 'nested', 'wheres' => $nested->state->wheres, 'boolean' => $this->boolean($boolean)];
        }
        return $this;
    }

    public function whereNull(string $column, string $boolean = 'AND', bool $not = false): static
    {
        SqlCompiler::identifier($column);
        $this->state->wheres[] = ['type' => 'null', 'column' => $column, 'boolean' => $this->boolean($boolean), 'not' => $not];
        return $this;
    }
    public function whereNotNull(string $column, string $boolean = 'AND'): static
    {
        return $this->whereNull($column, $boolean, true);
    }
    public function whereIn(string $column, array $values, string $boolean = 'AND', bool $not = false): static
    {
        SqlCompiler::identifier($column);
        foreach ($values as $value) {
            SqlCompiler::placeholder($value);
        }
        $this->state->wheres[] = ['type' => 'in', 'column' => $column, 'values' => array_values($values), 'boolean' => $this->boolean($boolean), 'not' => $not];
        return $this;
    }
    public function whereNotIn(string $column, array $values): static
    {
        return $this->whereIn($column, $values, 'AND', true);
    }
    public function whereBetween(string $column, array $values, string $boolean = 'AND', bool $not = false): static
    {
        if (count($values) !== 2 || in_array(null, $values, true)) {
            throw new \InvalidArgumentException('Between requires two non-null values.');
        }
        SqlCompiler::identifier($column);
        foreach ($values as $value) {
            SqlCompiler::placeholder($value);
        }
        $this->state->wheres[] = ['type' => 'between', 'column' => $column, 'values' => array_values($values), 'boolean' => $this->boolean($boolean), 'not' => $not];
        return $this;
    }
    public function whereColumn(string $column, string $operator, ?string $value = null): static
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        SqlCompiler::identifier($column);
        SqlCompiler::identifier($value);
        $this->state->wheres[] = ['type' => 'column', 'column' => $column, 'operator' => $this->operator($operator), 'value' => $value, 'boolean' => 'AND'];
        return $this;
    }

    public function select(array|string $columns = ['*'], string ...$additional): static
    {
        $columns = is_array($columns) ? $columns : array_merge([$columns], $additional);
        foreach ($columns as $column) {
            SqlCompiler::column($column);
        }
        $this->state->columns = $columns ?: ['*'];
        return $this;
    }
    public function distinct(bool $value = true): static
    {
        $this->state->distinct = $value;
        return $this;
    }
    public function addSelect(array|string $column): static
    {
        $columns = is_array($column) ? $column : func_get_args();
        foreach ($columns as $item) {
            SqlCompiler::column($item);
        }
        $this->state->columns = array_merge($this->state->columns === ['*'] ? [] : $this->state->columns, $columns);
        return $this;
    }
    public function from(string $table): static
    {
        $this->table = $this->connection->getPrefix() . $table;
        SqlCompiler::identifier($this->table);
        return $this;
    }
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): static
    {
        $type = strtoupper($type);
        if (!in_array($type, ['INNER', 'LEFT', 'RIGHT'], true)) {
            throw new \InvalidArgumentException('Invalid join type.');
        }
        SqlCompiler::identifier($table);
        SqlCompiler::identifier($first);
        SqlCompiler::identifier($second);
        $operator = $this->operator($operator);
        $this->state->joins[] = compact('table', 'first', 'operator', 'second', 'type');
        return $this;
    }
    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }
    public function having(string $column, string $operator, mixed $value): static
    {
        $query = new Builder($this->connection, $this->table);
        $query->where($column, $operator, $value);
        array_push($this->state->havings, ...$query->state->wheres);
        return $this;
    }
    public function groupBy(string ...$columns): static
    {
        foreach ($columns as $column) {
            SqlCompiler::identifier($column);
        }
        $this->state->groups = $columns;
        return $this;
    }
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        SqlCompiler::identifier($column);
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            throw new \InvalidArgumentException('Invalid order direction.');
        }
        $this->state->orders[] = [$column, $direction];
        return $this;
    }
    public function limit(int $limit): static
    {
        if ($limit < 0) {
            throw new \InvalidArgumentException('Limit cannot be negative.');
        }
        $this->state->limit = $limit;
        return $this;
    }
    public function offset(int $offset): static
    {
        if ($offset < 0) {
            throw new \InvalidArgumentException('Offset cannot be negative.');
        }
        $this->state->offset = $offset;
        return $this;
    }
    public function lockForUpdate(): static
    {
        $this->state->lock = 'FOR UPDATE';
        return $this;
    }
    public function when(mixed $value, callable $callback, ?callable $otherwise = null): mixed
    {
        $value = $value instanceof \Closure ? $value($this) : $value;
        return $value ? ($callback($this, $value) ?? $this) : ($otherwise ? ($otherwise($this, $value) ?? $this) : $this);
    }
    public function unless(mixed $value, callable $callback, ?callable $otherwise = null): mixed
    {
        return $this->when(!$value, $callback, $otherwise);
    }
    public function tap(callable $callback): static
    {
        $callback($this);
        return $this;
    }
    public function take(int $value): static
    {
        return $this->limit($value);
    }
    public function skip(int $value): static
    {
        return $this->offset($value);
    }
    public function forPage(int $page, int $perPage = 15): static
    {
        return $this->offset((max(1, $page) - 1) * $perPage)->limit($perPage);
    }
    public function orderByDesc(string $column): static
    {
        return $this->orderBy($column, 'desc');
    }
    public function latest(string $column = 'created_at'): static
    {
        return $this->orderByDesc($column);
    }
    public function oldest(string $column = 'created_at'): static
    {
        return $this->orderBy($column);
    }
    public function reorder(?string $column = null, string $direction = 'asc'): static
    {
        $this->state->orders = [];
        return $column === null ? $this : $this->orderBy($column, $direction);
    }
    public function orWhereNull(string $column): static
    {
        return $this->whereNull($column, 'OR');
    }
    public function orWhereNotNull(string $column): static
    {
        return $this->whereNotNull($column, 'OR');
    }
    public function orWhereIn(string $column, array $values): static
    {
        return $this->whereIn($column, $values, 'OR');
    }
    public function whereNotBetween(string $column, array $values): static
    {
        return $this->whereBetween($column, $values, 'AND', true);
    }
    public function orWhereBetween(string $column, array $values): static
    {
        return $this->whereBetween($column, $values, 'OR');
    }
    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    protected function executionState(): QueryState
    {
        return clone $this->state;
    }
    protected function setExecutionState(QueryState $state): void
    {
        $this->state = $state;
    }
    public function toSql(): string
    {
        return str_replace(['%s', '%d', '%f'], '?', $this->compiler->select($this->table, $this->executionState())[0]);
    }
    public function getBindings(): array
    {
        return $this->compiler->select($this->table, $this->executionState())[1];
    }
    public function get(array|string $columns = ['*']): Collection
    {
        $state = $this->executionState();
        if ($state->columns === ['*']) {
            $state->columns = (array) $columns;
        }
        [$sql, $bindings] = $this->compiler->select($this->table, $state);
        return new Collection($this->connection->select($sql, $bindings));
    }
    public function first(array|string $columns = ['*']): ?object
    {
        return (clone $this)->limit(1)->get($columns)->first();
    }
    public function firstOrFail(array|string $columns = ['*']): object
    {
        return $this->first($columns) ?? throw new ModelNotFoundException('No matching record found.');
    }
    public function find(mixed $id, array|string $columns = ['*']): ?object
    {
        return (clone $this)->where('id', $id)->first($columns);
    }
    public function findOrFail(mixed $id, array|string $columns = ['*']): object
    {
        return $this->find($id, $columns) ?? throw new ModelNotFoundException('No matching record found.');
    }
    public function exists(): bool
    {
        $state = $this->executionState();
        if ($state->limit === 0) {
            return false;
        }
        $state->limit = 1;
        [$sql, $bindings] = $this->compiler->select($this->table, $state);
        return (bool) ($this->connection->select('SELECT EXISTS(' . $sql . ') AS aggregate', $bindings)[0]->aggregate ?? false);
    }
    public function count(string $column = '*'): int
    {
        $state = $this->executionState();
        if (!$state->groups && !$state->havings && (!$state->distinct || $column !== '*')) {
            $state->columns = ['*'];
            $state->orders = [];
            $state->limit = $state->offset = null;
            $state->lock = null;
            $distinct = $state->distinct;
            $state->distinct = false;
            [$sql, $bindings] = $this->compiler->select($this->table, $state);
            $sql = 'SELECT COUNT(' . ($distinct ? 'DISTINCT ' : '') . SqlCompiler::identifier($column, true) . ') AS aggregate' . substr($sql, strlen('SELECT *'));
            return (int) ($this->connection->select($sql, $bindings)[0]->aggregate ?? 0);
        }
        if ($column !== '*') {
            $state->columns = [$column . ' AS bluwp_count_value'];
        }
        if ($column === '*' && !$state->distinct && !$state->havings) {
            $state->columns = $state->groups ?: [$this->table . '.*'];
        }
        $state->orders = [];
        $state->limit = $state->offset = null;
        $state->lock = null;
        [$sql, $bindings] = $this->compiler->select($this->table, $state);
        $rows = $this->connection->select('SELECT COUNT(' . ($column === '*' ? '*' : 'bluwp_count_value') . ') AS aggregate FROM (' . $sql . ') AS bluwp_count', $bindings);
        return (int) ($rows[0]->aggregate ?? 0);
    }
    public function aggregate(string $function, string $column): mixed
    {
        $function = strtoupper($function);
        if (!in_array($function, ['SUM', 'AVG', 'MIN', 'MAX'], true)) {
            throw new \InvalidArgumentException('Unsupported aggregate.');
        }
        SqlCompiler::identifier($column);
        $state = $this->executionState();
        if ($state->groups || $state->havings) {
            throw new \LogicException('Scalar aggregates do not support grouped queries.');
        }
        $state->columns = [$column . ' AS bluwp_value'];
        $state->orders = [];
        $state->limit = $state->offset = null;
        $state->lock = null;
        [$sql, $bindings] = $this->compiler->select($this->table, $state);
        $rows = $this->connection->select('SELECT ' . $function . '(' . SqlCompiler::identifier('bluwp_value')
            . ') AS aggregate FROM (' . $sql . ') AS bluwp_aggregate', $bindings);
        return $rows[0]->aggregate ?? null;
    }
    public function sum(string $column): int|float
    {
        return 0 + ($this->aggregate('SUM', $column) ?? 0);
    }
    public function avg(string $column): ?float
    {
        $value = $this->aggregate('AVG', $column);
        return $value === null ? null : (float) $value;
    }
    public function min(string $column): mixed
    {
        return $this->aggregate('MIN', $column);
    }
    public function max(string $column): mixed
    {
        return $this->aggregate('MAX', $column);
    }
    public function pluck(string $column, ?string $key = null): Collection
    {
        SqlCompiler::identifier($column);
        if ($key !== null) {
            SqlCompiler::identifier($key);
        }
        $valueParts = explode('.', $column);
        $keyParts = $key === null ? [] : explode('.', $key);
        $valueName = end($valueParts);
        $keyName = $key === null ? null : end($keyParts);
        $columns = $key === null ? [$column] : [$column, $key];
        $query = clone $this;
        $query->select(...$columns);
        [$sql, $bindings] = $this->compiler->select($this->table, $query->executionState());
        $result = [];
        foreach ($this->connection->select($sql, $bindings) as $row) {
            $value = $row->{$valueName} ?? null;
            if ($keyName === null) {
                $result[] = $value;
            } else {
                $result[$row->{$keyName}] = $value;
            }
        }
        return new Collection($result);
    }
    public function value(string $column): mixed
    {
        return (clone $this)->limit(1)->pluck($column)[0] ?? null;
    }
    public function paginate(?int $perPage = 15, array|string $columns = ['*'], string $pageName = 'page', ?int $page = null, int|\Closure|null $total = null): LengthAwarePaginator
    {
        $perPage ??= 15;
        $page ??= Paginator::resolveCurrentPage($pageName);
        if ($perPage < 1 || $page < 1 || $page > intdiv(PHP_INT_MAX, $perPage)) {
            throw new \InvalidArgumentException('Invalid pagination.');
        }
        $total = $total instanceof \Closure ? $total() : ($total ?? $this->count());
        $offset = ($page - 1) * $perPage;
        $items = (clone $this)->limit($perPage)->offset($offset)->get($columns);
        return new LengthAwarePaginator($items, $total, $perPage, $page, ['path' => Paginator::resolveCurrentPath(), 'pageName' => $pageName]);
    }
    public function simplePaginate(?int $perPage = 15, array|string $columns = ['*'], string $pageName = 'page', ?int $page = null): Paginator
    {
        $perPage ??= 15;
        $page ??= Paginator::resolveCurrentPage($pageName);
        if ($perPage < 1 || $perPage >= PHP_INT_MAX || $page < 1 || $page > intdiv(PHP_INT_MAX, $perPage)) {
            throw new \InvalidArgumentException('Invalid pagination.');
        }
        return new Paginator(
            (clone $this)->offset(($page - 1) * $perPage)->limit($perPage + 1)->get($columns),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => $pageName]
        );
    }
    public function cursorPaginate(?int $perPage = 15, array|string $columns = ['*'], string $cursorName = 'cursor', Cursor|string|null $cursor = null): CursorPaginator
    {
        $perPage ??= 15;
        if ($perPage < 1 || $perPage >= PHP_INT_MAX) {
            throw new \InvalidArgumentException('Invalid page size.');
        }
        $cursor = is_string($cursor) ? Cursor::fromEncoded($cursor) : ($cursor ?? CursorPaginator::resolveCurrentCursor($cursorName));
        $query = clone $this;
        $state = $query->executionState();
        if (!$state->orders) {
            throw new \LogicException('Cursor pagination requires deterministic orderBy with a unique tie-breaker.');
        }
        if ($state->groups || $state->havings || $state->distinct) {
            throw new \LogicException('Cursor pagination does not support grouped or distinct queries.');
        }
        $query->setExecutionState($state);
        $query->state->limit = $query->state->offset = null;
        if ($cursor) {
            if ($query->state->wheres) {
                $query->state->wheres = [['type' => 'nested', 'wheres' => $query->state->wheres, 'boolean' => 'AND']];
            }
            $query->whereGroup(function ($outer) use ($state, $cursor): void {
                foreach ($state->orders as $index => [$column, $direction]) {
                    $outer->whereGroup(function ($inner) use ($state, $cursor, $index, $column, $direction): void {
                        for ($prior = 0; $prior < $index; ++$prior) {
                            $previous = $state->orders[$prior][0];
                            $inner->where($previous, '=', $cursor->parameter($previous));
                        }
                        $greater = ($direction === 'ASC') === $cursor->pointsToNextItems();
                        $inner->where($column, $greater ? '>' : '<', $cursor->parameter($column));
                    }, $index === 0 ? 'AND' : 'OR');
                }
            });
            if ($cursor->pointsToPreviousItems()) {
                $query->state->orders = array_map(static fn ($order) => [$order[0], $order[1] === 'ASC' ? 'DESC' : 'ASC'], $state->orders);
            }
        }
        return new CursorPaginator(
            $query->limit($perPage + 1)->get($columns),
            $perPage,
            $cursor,
            ['path' => Paginator::resolveCurrentPath(), 'cursorName' => $cursorName, 'parameters' => array_column($state->orders, 0)]
        );
    }
    protected function defaultKeyName(): string
    {
        return 'id';
    }
    public function toCompiledSql(): array
    {
        return $this->compiler->select($this->table, $this->executionState());
    }
    public function explain(): Collection
    {
        [$sql, $bindings] = $this->toCompiledSql();
        return new Collection($this->connection->select('EXPLAIN ' . $sql, $bindings));
    }
    public function selectCount(): static
    {
        $this->state->columns = [new Expression('COUNT(*)')];
        $this->state->orders = [];
        return $this;
    }
    public function addSubquery(Builder $query, string $as, bool $exists = false): static
    {
        [$sql, $bindings] = $query->toCompiledSql();
        if ($this->state->columns === ['*']) {
            $this->state->columns = [$this->table . '.*'];
        }
        $this->state->columns[] = new Expression(($exists ? 'EXISTS' : '') . '(' . $sql . ') AS ' . SqlCompiler::identifier($as), $bindings);
        return $this;
    }
    public function whereSub(Builder $query, string $operator, mixed $value, string $boolean = 'AND'): static
    {
        [$sql, $bindings] = $query->toCompiledSql();
        $operator = $this->operator($operator);
        SqlCompiler::placeholder($value);
        $this->state->wheres[] = compact('sql', 'bindings', 'operator', 'value') + ['type' => 'subquery', 'boolean' => $this->boolean($boolean)];
        return $this;
    }
    public function whereExists(Builder|\Closure $query, string $boolean = 'AND', bool $not = false): static
    {
        if ($query instanceof \Closure) {
            $callback = $query;
            $query = new self($this->connection, $this->table);
            $callback($query);
        }
        [$sql, $bindings] = $query->toCompiledSql();
        $this->state->wheres[] = ['type' => 'exists', 'sql' => $sql, 'bindings' => $bindings, 'not' => $not, 'boolean' => $this->boolean($boolean)];
        return $this;
    }
    public function whereNotExists(Builder|\Closure $query, string $boolean = 'AND'): static
    {
        return $this->whereExists($query, $boolean, true);
    }
    public function orWhereExists(Builder|\Closure $query): static
    {
        return $this->whereExists($query, 'OR');
    }
    public function selectSub(Builder $query, string $as): static
    {
        [$sql, $bindings] = $query->toCompiledSql();
        if ($this->state->columns === ['*']) {
            $this->state->columns = [];
        }
        $this->state->columns[] = new Expression('(' . $sql . ') AS ' . SqlCompiler::identifier($as), $bindings);
        return $this;
    }
    private function jsonColumn(string $column): array
    {
        $parts = explode('->', $column);
        $name = array_shift($parts);
        SqlCompiler::identifier($name);
        $path = '$';
        foreach ($parts as $part) {
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $part)) {
                throw new \InvalidArgumentException('Invalid JSON path.');
            }
            $path .= '.' . $part;
        }
        return [$name, $path];
    }
    public function whereJsonContains(string $column, mixed $value, string $boolean = 'AND', bool $not = false): static
    {
        [$column, $path] = $this->jsonColumn($column);
        $value = json_encode($value, JSON_THROW_ON_ERROR);
        $this->state->wheres[] = compact('column', 'path', 'value', 'not') + ['type' => 'jsonContains', 'boolean' => $this->boolean($boolean)];
        return $this;
    }
    public function whereJsonLength(string $column, mixed $operator, ?int $value = null): static
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        if (!is_int($value)) {
            throw new \InvalidArgumentException('JSON length must be an integer.');
        }
        [$column, $path] = $this->jsonColumn($column);
        $operator = $this->operator($operator);
        $this->state->wheres[] = compact('column', 'path', 'value', 'operator') + ['type' => 'jsonLength', 'boolean' => 'AND'];
        return $this;
    }
    public function increment(string $column, int|float $amount = 1, array $extra = []): int
    {
        $bindings = [];
        $expression = new Expression(SqlCompiler::identifier($column) . ' + ' . SqlCompiler::bind($amount, $bindings), $bindings);
        $this->assertWriteAllowed();
        [$sql, $values] = $this->compiler->write($this->table, $this->executionState(), array_merge($extra, [$column => $expression]));
        return $this->connection->statement($sql, $values);
    }
    public function decrement(string $column, int|float $amount = 1, array $extra = []): int
    {
        return $this->increment($column, -$amount, $extra);
    }
    public function lazy(int $chunkSize = 1000): \Forouzandea\Bluwp\Support\LazyCollection
    {
        if ($chunkSize < 1) {
            throw new \InvalidArgumentException('Chunk size must be positive.');
        }
        $query = clone $this;
        $state = $query->executionState();
        if (!$state->orders) {
            throw new \LogicException('Lazy queries require an explicit orderBy.');
        }
        $query->setExecutionState($state);
        return new \Forouzandea\Bluwp\Support\LazyCollection(function () use ($query, $state, $chunkSize) {
            $remaining = $state->limit;
            $offset = $state->offset ?? 0;
            $key = 0;
            while ($remaining === null || $remaining > 0) {
                $size = $remaining === null ? $chunkSize : min($chunkSize, $remaining);
                $items = (clone $query)->limit($size)->offset($offset)->get();
                foreach ($items as $item) {
                    yield $key++ => $item;
                }
                if ($items->count() < $size) {
                    break;
                }
                $offset += $size;
                if ($remaining !== null) {
                    $remaining -= $size;
                }
            }
        });
    }
    public function lazyById(int $chunkSize = 1000, ?string $column = null, ?string $alias = null): \Forouzandea\Bluwp\Support\LazyCollection
    {
        if ($chunkSize < 1 || $this->hasLimitOrOffset()) {
            throw new \InvalidArgumentException('Keyset iteration requires positive size and no limit/offset.');
        }
        $column ??= $this->defaultKeyName();
        $parts = explode('.', $column);
        $alias ??= end($parts);
        SqlCompiler::identifier($column);
        $query = clone $this;
        $state = $query->executionState();
        if ($state->groups || $state->havings || $state->distinct) {
            throw new \LogicException('Keyset iteration requires ungrouped rows.');
        }
        if ($state->wheres) {
            $state->wheres = [['type' => 'nested', 'wheres' => $state->wheres, 'boolean' => 'AND']];
        }
        $query->setExecutionState($state);
        return new \Forouzandea\Bluwp\Support\LazyCollection(function () use ($query, $chunkSize, $column, $alias) {
            $last = null;
            $key = 0;
            do {
                $page = (clone $query)->reorder($column)->limit($chunkSize);
                if ($last !== null) {
                    $page->where($column, '>', $last);
                }
                $items = $page->get();
                if ($items->isEmpty()) {
                    break;
                }
                $next = $items->last()->{$alias} ?? null;
                if ($next === null || $next === $last) {
                    throw new \LogicException('Select a unique iteration key.');
                }
                foreach ($items as $item) {
                    yield $key++ => $item;
                }
                $last = $next;
            } while ($items->count() === $chunkSize);
        });
    }
    public function chunkById(int $size, callable $callback, ?string $column = null, ?string $alias = null): bool
    {
        $column ??= $this->defaultKeyName();
        $parts = explode('.', $column);
        $alias ??= end($parts);
        $state = $this->executionState();
        if ($size < 1 || $state->groups || $state->havings || $state->distinct || $state->offset !== null || $state->limit !== null) {
            throw new \InvalidArgumentException('Chunking requires a positive size and an unpaginated, ungrouped query.');
        }
        $last = null;
        do {
            $query = clone $this;
            $query->setExecutionState(clone $state);
            $query->state->orders = [];
            if ($query->state->wheres) {
                $query->state->wheres = [['type' => 'nested', 'wheres' => $query->state->wheres, 'boolean' => 'AND']];
            }
            if ($last !== null) {
                $query->where($column, '>', $last);
            }
            $items = $query->orderBy($column)->limit($size)->get();
            if ($items->isEmpty()) {
                break;
            }
            $values = array_values($items->all());
            $next = $values[count($values) - 1]->{$alias} ?? null;
            if ($next === null || $next === $last) {
                throw new \LogicException('Chunk key must be selected and unique.');
            }
            if ($callback($items) === false) {
                return false;
            }
            $last = $next;
        } while (count($items) === $size);
        return true;
    }
    public function chunk(int $count, callable $callback): bool
    {
        if ($count < 1) {
            throw new \InvalidArgumentException('Chunk size must be positive.');
        }
        $state = $this->executionState();
        if (!$state->orders) {
            throw new \LogicException('Chunking requires an explicit orderBy.');
        }
        $offset = $state->offset ?? 0;
        $remaining = $state->limit;
        for ($page = 1; $remaining === null || $remaining > 0; ++$page) {
            $size = $remaining === null ? $count : min($count, $remaining);
            $query = clone $this;
            $query->setExecutionState(clone $state);
            $items = $query->offset($offset)->limit($size)->get();
            if ($items->isEmpty()) {
                break;
            }
            if ($callback($items, $page) === false) {
                return false;
            }
            $offset += $items->count();
            if ($remaining !== null) {
                $remaining -= $items->count();
            }
            if ($items->count() < $size) {
                break;
            }
        }
        return true;
    }
    public function insert(array $data): bool
    {
        if (!$data) {
            return true;
        }
        $rows = is_array(reset($data)) ? $data : [$data];
        $first = reset($rows);
        $columns = array_keys($first);
        $bindings = [];
        $groups = [];
        foreach ($rows as $row) {
            if (!is_array($row) || count($row) !== count($first) || array_diff_key($first, $row)) {
                throw new \InvalidArgumentException('All inserted rows must have identical columns.');
            }
            $values = [];
            foreach ($columns as $column) {
                $values[] = SqlCompiler::bind($row[$column], $bindings);
            }
            $groups[] = '(' . implode(', ', $values) . ')';
        }
        $this->connection->statement('INSERT INTO ' . SqlCompiler::identifier($this->table) . ' ('
            . implode(', ', array_map([SqlCompiler::class, 'identifier'], $columns)) . ') VALUES ' . implode(', ', $groups), $bindings);
        return true;
    }
    public function insertGetId(array $values, ?string $sequence = null): int
    {
        return $this->connection->insert($this->table, $values);
    }
    public function updateOrInsert(array $attributes, array $values = []): bool
    {
        $query = clone $this;
        $query->where($attributes);
        return $query->exists() ? ($values === [] || $query->limit(1)->update($values) > 0) : $this->insert(array_merge($attributes, $values));
    }
    public function upsert(array $rows, array|string $uniqueBy, ?array $updateColumns = null): int
    {
        $uniqueBy = (array) $uniqueBy;
        if (!$rows) {
            return 0;
        }
        if (!is_array(reset($rows))) {
            $rows = [$rows];
        }
        $updateColumns ??= array_keys(reset($rows));
        if (!$uniqueBy || !$updateColumns) {
            throw new \InvalidArgumentException('Upsert requires conflict and update columns.');
        }
        $first = reset($rows);
        if (!is_array($first) || !$first) {
            throw new \InvalidArgumentException('Upsert requires non-empty row arrays.');
        }
        $columns = array_keys($first);
        foreach (array_merge($columns, $uniqueBy, $updateColumns) as $column) {
            SqlCompiler::identifier($column);
            if (!in_array($column, $columns, true)) {
                throw new \InvalidArgumentException('Upsert column missing from rows.');
            }
        }
        $bindings = [];
        $groups = [];
        foreach ($rows as $row) {
            if (!is_array($row) || count($row) !== count($columns) || array_diff_key($first, $row)) {
                throw new \InvalidArgumentException('All upsert rows must have the same columns.');
            }
            $values = [];
            foreach ($columns as $column) {
                $values[] = SqlCompiler::bind($row[$column], $bindings);
            }
            $groups[] = '(' . implode(', ', $values) . ')';
        }
        $updates = array_map(static fn (string $column): string =>
            SqlCompiler::identifier($column) . ' = VALUES(' . SqlCompiler::identifier($column) . ')', $updateColumns);
        // MySQL resolves conflicts using the table's actual primary/unique indexes.
        return $this->connection->statement('INSERT INTO ' . SqlCompiler::identifier($this->table)
            . ' (' . implode(', ', array_map([SqlCompiler::class, 'identifier'], $columns)) . ') VALUES '
            . implode(', ', $groups) . ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates), $bindings);
    }
    public function allowAllRows(): static
    {
        $this->allowUnconstrained = true;
        return $this;
    }
    public function requireWhere(): static
    {
        $this->allowUnconstrained = false;
        return $this;
    }
    protected function assertWriteAllowed(): void
    {
        if (!$this->state->wheres && !$this->allowUnconstrained) {
            throw new \LogicException('Unconstrained write requires explicit allowAllRows().');
        }
    }
    public function update(array $data): int
    {
        $this->assertWriteAllowed();
        [$sql, $bindings] = $this->compiler->write($this->table, $this->executionState(), $data);
        return $this->connection->statement($sql, $bindings);
    }
    public function delete(): int
    {
        $this->assertWriteAllowed();
        [$sql, $bindings] = $this->compiler->write($this->table, $this->executionState(), null);
        return $this->connection->statement($sql, $bindings);
    }
}
