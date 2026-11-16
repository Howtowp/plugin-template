<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support;

use Howtowp\Bluwp\Contracts\Arrayable;
use Howtowp\Bluwp\Contracts\Jsonable;

/**
 * @template TKey of array-key
 * @template TValue
 * @implements \IteratorAggregate<TKey, TValue>
 * @phpstan-consistent-constructor
 */
class Collection implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable, Arrayable, Jsonable
{
    protected array $items;

    public function __construct(mixed $items = [])
    {
        $this->items = match (true) {
            $items instanceof self => $items->all(),
            $items instanceof Arrayable => $items->toArray(),
            $items instanceof \Traversable => iterator_to_array($items),
            $items instanceof \JsonSerializable => (array)$items->jsonSerialize(),
            default => (array)$items,
        };
    }

    public static function make(mixed $items = []): static
    {
        return new static($items);
    }

    public function all(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    protected static function value(mixed $value): mixed
    {
        return $value instanceof \Closure ? $value() : $value;
    }

    public static function dataGet(mixed $target, string|int|null $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return $target;
        }
        foreach (explode('.', (string)$key) as $part) {
            if (is_array($target) && array_key_exists($part, $target)) {
                $target = $target[$part];
            } elseif ($target instanceof \ArrayAccess && $target->offsetExists($part)) {
                $target = $target[$part];
            } elseif (is_object($target) && (isset($target->$part) || array_key_exists($part, get_object_vars($target)))) {
                $target = $target->$part;
            } else {
                return self::value($default);
            }
        }
        return $target;
    }

    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        foreach ($this->items as $key => $item) {
            if ($callback === null || $callback($item, $key)) {
                return $item;
            }
        }
        return self::value($default);
    }

    public function last(?callable $callback = null, mixed $default = null): mixed
    {
        foreach (array_reverse($this->items, true) as $key => $item) {
            if ($callback === null || $callback($item, $key)) {
                return $item;
            }
        }
        return self::value($default);
    }

    public function get(string|int $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->items) ? $this->items[$key] : self::value($default);
    }

    public function has(string|int|array $key): bool
    {
        foreach ((array)$key as $item) {
            if (!array_key_exists($item, $this->items)) {
                return false;
            }
        }
        return true;
    }

    public function map(callable $callback): self
    {
        $items = [];
        foreach ($this->items as $key => $value) {
            $items[$key] = $callback($value, $key);
        }
        return new static($items);
    }

    public function transform(callable $callback): static
    {
        $this->items = $this->map($callback)->all();
        return $this;
    }

    public function each(callable $callback): static
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }
        return $this;
    }

    public function filter(?callable $callback = null): static
    {
        return new static($callback ? array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH) : array_filter($this->items));
    }

    public function reject(callable $callback): static
    {
        return $this->filter(static fn ($item, $key) => !$callback($item, $key));
    }

    public function values(): static
    {
        return new static(array_values($this->items));
    }

    public function keys(): static
    {
        return new static(array_keys($this->items));
    }

    public function pluck(string|int|null $value, string|int|null $key = null): self
    {
        $result = [];
        foreach ($this->items as $item) {
            $entry = self::dataGet($item, $value);
            if ($key === null) {
                $result[] = $entry;
            } else {
                $result[self::dataGet($item, $key)] = $entry;
            }
        }
        return new self($result);
    }

    public function keyBy(string|callable $key): static
    {
        $result = [];
        foreach ($this->items as $index => $item) {
            $resolved = !is_string($key) ? $key($item, $index) : self::dataGet($item, $key);
            $result[(string)$resolved] = $item;
        }
        return new static($result);
    }

    public function groupBy(string|callable $key, bool $preserveKeys = false): self
    {
        $groups = [];
        foreach ($this->items as $index => $item) {
            $keys = !is_string($key) ? $key($item, $index) : self::dataGet($item, $key);
            foreach (is_array($keys) ? $keys : [$keys] as $groupKey) {
                $groupKey = is_bool($groupKey) ? (int)$groupKey : $groupKey;
                $groups[$groupKey] ??= new static();
                $groups[$groupKey]->offsetSet($preserveKeys ? $index : null, $item);
            }
        }
        return new self($groups);
    }

    private static function compare(mixed $actual, string $operator, mixed $value): bool
    {
        return match ($operator) {
            '=', '==' => $actual == $value,
            '===' => $actual === $value,
            '!=', '<>' => $actual != $value,
            '!==' => $actual !== $value,
            '>' => $actual > $value,
            '>=' => $actual >= $value,
            '<' => $actual < $value,
            '<=' => $actual <= $value,
            default => throw new \InvalidArgumentException('Invalid collection operator.'),
        };
    }

    public function where(string $key, mixed $operator = null, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->filter(static fn ($item) => self::compare(self::dataGet($item, $key), $operator ?? '=', $value));
    }

    public function whereIn(string $key, iterable $values, bool $strict = false): static
    {
        $values = (new self($values))->all();
        return $this->filter(static fn ($item) => in_array(self::dataGet($item, $key), $values, $strict));
    }

    public function whereNull(string $key): static
    {
        return $this->where($key, '===', null);
    }

    public function firstWhere(string $key, mixed $operator = null, mixed $value = null): mixed
    {
        if (func_num_args() === 1) {
            return $this->first(static fn ($item) => (bool)self::dataGet($item, $key));
        }
        return $this->where(...func_get_args())->first();
    }

    public function contains(mixed $key, mixed $operator = null, mixed $value = null): bool
    {
        if (func_num_args() === 1) {
            if (!is_string($key) && is_callable($key)) {
                foreach ($this->items as $index => $item) {
                    if ($key($item, $index)) {
                        return true;
                    }
                }
                return false;
            }
            return in_array($key, $this->items);
        }
        return !$this->where(...func_get_args())->isEmpty();
    }

    public function containsStrict(mixed $value): bool
    {
        return in_array($value, $this->items, true);
    }

    public function unique(string|callable|null $key = null, bool $strict = false): static
    {
        $seen = [];
        return $this->filter(static function ($item, $index) use (&$seen, $key, $strict) {
            $value = !is_string($key) && is_callable($key) ? $key($item, $index) : self::dataGet($item, $key);
            if (in_array($value, $seen, $strict)) {
                return false;
            }
            $seen[] = $value;
            return true;
        });
    }

    public function sortBy(string|callable $key, int $options = SORT_REGULAR, bool $descending = false): static
    {
        $sort = [];
        foreach ($this->items as $index => $item) {
            $sort[$index] = !is_string($key) ? $key($item, $index) : self::dataGet($item, $key);
        }
        $descending ? arsort($sort, $options) : asort($sort, $options);
        $items = [];
        foreach ($sort as $index => $_) {
            $items[$index] = $this->items[$index];
        }
        return new static($items);
    }

    public function sortByDesc(string|callable $key, int $options = SORT_REGULAR): static
    {
        return $this->sortBy($key, $options, true);
    }

    public function reverse(): static
    {
        return new static(array_reverse($this->items, true));
    }

    public function slice(int $offset, ?int $length = null): static
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }

    public function take(int $limit): static
    {
        return $limit < 0 ? $this->slice($limit) : $this->slice(0, $limit);
    }

    public function skip(int $count): static
    {
        return $this->slice($count);
    }

    public function forPage(int $page, int $perPage): static
    {
        return $this->slice(max(0, ($page - 1) * $perPage), $perPage);
    }

    public function chunk(int $size): self
    {
        if ($size < 1) {
            return new self();
        }
        return new self(array_map(static fn ($items) => new static($items), array_chunk($this->items, $size, true)));
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        foreach ($this->items as $key => $item) {
            $initial = $callback($initial, $item, $key);
        }
        return $initial;
    }

    public function sum(string|callable|null $key = null): int|float
    {
        return $this->reduce(static fn ($sum, $item) => $sum + (!is_string($key) && is_callable($key) ? $key($item) : self::dataGet($item, $key)), 0);
    }

    public function avg(string|callable|null $key = null): int|float|null
    {
        $values = $this->map(static fn ($item) => !is_string($key) && is_callable($key) ? $key($item) : self::dataGet($item, $key))->filter(static fn ($v) => $v !== null);
        return $values->isEmpty() ? null : $values->sum() / $values->count();
    }

    public function min(?string $key = null): mixed
    {
        $values = $this->pluck($key)->filter(static fn ($v) => $v !== null)->all();
        return $values ? min($values) : null;
    }

    public function max(?string $key = null): mixed
    {
        $values = $this->pluck($key)->filter(static fn ($v) => $v !== null)->all();
        return $values ? max($values) : null;
    }

    public function merge(iterable $items): static
    {
        return new static(array_merge($this->items, (new self($items))->all()));
    }

    public function concat(iterable $items): static
    {
        $result = clone $this;
        foreach ($items as $item) {
            $result->push($item);
        }
        return $result;
    }

    public function push(mixed ...$values): static
    {
        array_push($this->items, ...$values);
        return $this;
    }

    public function prepend(mixed $value, string|int|null $key = null): static
    {
        if ($key === null) {
            array_unshift($this->items, $value);
        } else {
            $this->items = [$key => $value] + $this->items;
        }
        return $this;
    }

    public function put(string|int $key, mixed $value): static
    {
        $this->items[$key] = $value;
        return $this;
    }

    public function forget(string|int|array $keys): static
    {
        foreach ((array)$keys as $key) {
            unset($this->items[$key]);
        }
        return $this;
    }

    public function only(array $keys): static
    {
        return new static(array_intersect_key($this->items, array_flip($keys)));
    }

    public function except(array $keys): static
    {
        return new static(array_diff_key($this->items, array_flip($keys)));
    }

    public function implode(string $value, ?string $glue = null): string
    {
        return $glue === null ? implode($value, $this->items) : implode($glue, $this->pluck($value)->all());
    }

    public function when(mixed $value, callable $callback, ?callable $default = null): mixed
    {
        $value = $value instanceof \Closure ? $value($this) : $value;
        return $value ? ($callback($this, $value) ?? $this) : ($default ? ($default($this, $value) ?? $this) : $this);
    }

    public function tap(callable $callback): static
    {
        $callback($this);
        return $this;
    }

    public function toArray(): array
    {
        return array_map(static fn ($item) => $item instanceof Arrayable ? $item->toArray() : $item, $this->items);
    }

    public function jsonSerialize(): array
    {
        return array_map(static fn ($item) => match (true) {
            $item instanceof \JsonSerializable => $item->jsonSerialize(),
            $item instanceof Arrayable => $item->toArray(),
            default => $item,
        }, $this->items);
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options | JSON_THROW_ON_ERROR);
    }

    public function toBase(): self
    {
        return new self($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }
}
