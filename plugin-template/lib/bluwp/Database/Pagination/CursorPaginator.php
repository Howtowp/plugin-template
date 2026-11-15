<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Pagination;

use Forouzandea\Bluwp\Support\Collection;
use Forouzandea\Bluwp\Contracts\Arrayable;
use Forouzandea\Bluwp\Contracts\Jsonable;

class CursorPaginator implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable, Arrayable, Jsonable
{
    private Collection $items;
    private bool $hasMore;
    private string $path;
    private string $cursorName;
    private array $query;
    private array $parameters;
    private ?string $fragment;
    private static ?\Closure $currentCursorResolver = null;
    public function __construct(iterable $items, private int $perPage, private ?Cursor $cursor = null, array $options = [])
    {
        if ($perPage < 1) {
            throw new \InvalidArgumentException('Per page must be positive.');
        }
        $items = $items instanceof Collection ? $items : new Collection($items);
        $this->hasMore = $items->count() > $perPage;
        $this->items = $items->slice(0, $perPage);
        if ($cursor?->pointsToPreviousItems()) {
            $this->items = $this->items->reverse()->values();
        }
        $this->path = rtrim($options['path'] ?? '/', '/') ?: '/';
        $this->cursorName = $options['cursorName'] ?? 'cursor';
        $this->query = $options['query'] ?? [];
        $this->parameters = $options['parameters'] ?? [];
        $this->fragment = $options['fragment'] ?? null;
    }
    public static function currentCursorResolver(\Closure $resolver): void
    {
        self::$currentCursorResolver = $resolver;
    }
    public static function resolveCurrentCursor(string $cursorName = 'cursor'): ?Cursor
    {
        $value = self::$currentCursorResolver ? (self::$currentCursorResolver)($cursorName) : null;
        return $value instanceof Cursor ? $value : Cursor::fromEncoded(is_string($value) ? $value : null);
    }
    private function cursorFor(mixed $item, bool $next): Cursor
    {
        $parameters = [];
        foreach ($this->parameters as $parameter) {
            $parts = explode('.', $parameter);
            $value = Collection::dataGet($item, end($parts));
            if ($value === null) {
                throw new \LogicException('Cursor ordering columns must be selected and non-null.');
            }
            $parameters[$parameter] = $value;
        }
        return new Cursor($parameters, $next);
    }
    public function nextCursor(): ?Cursor
    {
        if ($this->items->isEmpty() || (!$this->hasMore && ($this->cursor === null || $this->cursor->pointsToNextItems()))) {
            return null;
        }
        return $this->cursorFor($this->items->last(), true);
    }
    public function previousCursor(): ?Cursor
    {
        if ($this->items->isEmpty() || $this->cursor === null || (!$this->hasMore && $this->cursor->pointsToPreviousItems())) {
            return null;
        }
        return $this->cursorFor($this->items->first(), false);
    }
    public function cursor(): ?Cursor
    {
        return $this->cursor;
    }
    public function url(?Cursor $cursor): string
    {
        $query = $this->query;
        unset($query[$this->cursorName]);
        if ($cursor) {
            $query[$this->cursorName] = $cursor->encode();
        }
        return $this->path . ($query ? (str_contains($this->path, '?') ? '&' : '?') . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '')
            . ($this->fragment !== null ? '#' . $this->fragment : '');
    }
    public function nextPageUrl(): ?string
    {
        $cursor = $this->nextCursor();
        return $cursor ? $this->url($cursor) : null;
    }
    public function previousPageUrl(): ?string
    {
        $cursor = $this->previousCursor();
        return $cursor ? $this->url($cursor) : null;
    }
    public function appends(array|string $key, mixed $value = null): static
    {
        foreach (is_array($key) ? $key : [$key => $value] as $name => $entry) {
            if ($name !== $this->cursorName) {
                $this->query[$name] = $entry;
            }
        }
        return $this;
    }
    public function withPath(string $path): static
    {
        $this->path = rtrim($path, '/') ?: '/';
        return $this;
    }
    public function setPath(string $path): static
    {
        return $this->withPath($path);
    }
    public function withQueryString(): static
    {
        return $this->appends(AbstractPaginator::resolveQueryString());
    }
    public function getCursorName(): string
    {
        return $this->cursorName;
    }
    public function setCursorName(string $name): static
    {
        $this->cursorName = $name;
        return $this;
    }
    public function fragment(?string $fragment = null): string|static|null
    {
        if (func_num_args() === 0) {
            return $this->fragment;
        }
        $this->fragment = $fragment;
        return $this;
    }
    public function path(): string
    {
        return $this->path;
    }
    public function perPage(): int
    {
        return $this->perPage;
    }
    public function items(): array
    {
        return $this->items->all();
    }
    public function getCollection(): Collection
    {
        return $this->items;
    }
    public function setCollection(Collection $items): static
    {
        $this->items = $items;
        return $this;
    }
    public function through(callable $callback): static
    {
        $this->items->transform($callback);
        return $this;
    }
    public function hasMorePages(): bool
    {
        return $this->hasMore;
    }
    public function onFirstPage(): bool
    {
        return $this->previousCursor() === null;
    }
    public function onLastPage(): bool
    {
        return $this->nextCursor() === null;
    }
    public function hasPages(): bool
    {
        return !$this->onFirstPage() || !$this->onLastPage();
    }
    public function count(): int
    {
        return $this->items->count();
    }
    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }
    public function isNotEmpty(): bool
    {
        return $this->items->isNotEmpty();
    }
    public function getIterator(): \Traversable
    {
        return $this->items->getIterator();
    }
    public function offsetExists(mixed $offset): bool
    {
        return $this->items->offsetExists($offset);
    }
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items->offsetGet($offset);
    }
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->items->offsetSet($offset, $value);
    }
    public function offsetUnset(mixed $offset): void
    {
        $this->items->offsetUnset($offset);
    }
    public function __call(string $method, array $arguments): mixed
    {
        return $this->items->$method(...$arguments);
    }
    public function toArray(): array
    {
        return ['data' => $this->items->toArray(), 'path' => $this->path, 'per_page' => $this->perPage,
            'next_cursor' => $this->nextCursor()?->encode(), 'next_page_url' => $this->nextPageUrl(),
            'prev_cursor' => $this->previousCursor()?->encode(), 'prev_page_url' => $this->previousPageUrl()];
    }
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options | JSON_THROW_ON_ERROR);
    }
}
