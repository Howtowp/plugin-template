<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database\Pagination;

use Howtowp\Bluwp\Support\Collection;
use Howtowp\Bluwp\Support\HtmlString;
use Howtowp\Bluwp\Contracts\Arrayable;
use Howtowp\Bluwp\Contracts\Jsonable;

abstract class AbstractPaginator implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable, Arrayable, Jsonable
{
    protected Collection $items;
    protected int $perPage;
    protected int $currentPage;
    protected string $path = '/';
    protected array $query = [];
    protected ?string $fragment = null;
    protected string $pageName = 'page';
    protected array $options;
    protected static ?\Closure $currentPageResolver = null;
    protected static ?\Closure $currentPathResolver = null;
    protected static ?\Closure $queryStringResolver = null;
    protected static ?\Closure $viewFactoryResolver = null;
    public int $onEachSide = 3;

    protected function configure(iterable $items, int $perPage, ?int $currentPage, array $options): void
    {
        if ($perPage < 1) {
            throw new \InvalidArgumentException('Per page must be positive.');
        }
        $this->options = $options;
        $this->perPage = $perPage;
        $this->pageName = (string) ($options['pageName'] ?? 'page');
        $this->path = rtrim((string) ($options['path'] ?? '/'), '/') ?: '/';
        $this->query = (array) ($options['query'] ?? []);
        $this->fragment = $options['fragment'] ?? null;
        $this->currentPage = max(1, $currentPage ?? static::resolveCurrentPage($this->pageName));
        $this->items = $items instanceof Collection ? $items : new Collection($items);
    }
    public static function currentPageResolver(\Closure $resolver): void
    {
        static::$currentPageResolver = $resolver;
    }
    public static function currentPathResolver(\Closure $resolver): void
    {
        static::$currentPathResolver = $resolver;
    }
    public static function queryStringResolver(\Closure $resolver): void
    {
        static::$queryStringResolver = $resolver;
    }
    public static function viewFactoryResolver(\Closure $resolver): void
    {
        static::$viewFactoryResolver = $resolver;
    }
    public static function resolveCurrentPage(string $pageName = 'page', int $default = 1): int
    {
        $page = static::$currentPageResolver ? (static::$currentPageResolver)($pageName) : $default;
        return filter_var($page, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
    }
    public static function resolveCurrentPath(string $default = '/'): string
    {
        return static::$currentPathResolver ? (string) (static::$currentPathResolver)() : $default;
    }
    public function items(): array
    {
        return $this->items->all();
    }
    public function getCollection(): Collection
    {
        return $this->items;
    }
    public function setCollection(Collection $collection): static
    {
        $this->items = $collection;
        return $this;
    }
    public function through(callable $callback): static
    {
        $this->items->transform($callback);
        return $this;
    }
    public function currentPage(): int
    {
        return $this->currentPage;
    }
    public function perPage(): int
    {
        return $this->perPage;
    }
    public function firstItem(): ?int
    {
        return $this->isEmpty() ? null : ($this->currentPage - 1) * $this->perPage + 1;
    }
    public function lastItem(): ?int
    {
        return $this->isEmpty() ? null : $this->firstItem() + $this->count() - 1;
    }
    public function onFirstPage(): bool
    {
        return $this->currentPage <= 1;
    }
    public function onLastPage(): bool
    {
        return !$this->hasMorePages();
    }
    public function hasPages(): bool
    {
        return !$this->onFirstPage() || $this->hasMorePages();
    }
    public function previousPageUrl(): ?string
    {
        return $this->onFirstPage() ? null : $this->url($this->currentPage - 1);
    }
    public function nextPageUrl(): ?string
    {
        return $this->hasMorePages() ? $this->url($this->currentPage + 1) : null;
    }
    public function path(): string
    {
        return $this->path;
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
    public function getPageName(): string
    {
        return $this->pageName;
    }
    public function setPageName(string $name): static
    {
        $this->pageName = $name;
        return $this;
    }
    public function getOptions(): array
    {
        return $this->options;
    }
    public function onEachSide(int $count): static
    {
        $this->onEachSide = max(0, $count);
        return $this;
    }
    public function appends(array|string|null $key, mixed $value = null): static
    {
        foreach (is_array($key) ? $key : ($key === null ? [] : [$key => $value]) as $name => $entry) {
            if ($name !== $this->pageName) {
                $this->query[$name] = $entry;
            }
        }
        return $this;
    }
    public function withQueryString(): static
    {
        return $this->appends(static::resolveQueryString());
    }
    public static function resolveQueryString(): array
    {
        return static::$queryStringResolver ? (array) (static::$queryStringResolver)() : [];
    }
    public function fragment(?string $fragment = null): string|static|null
    {
        if (func_num_args() === 0) {
            return $this->fragment;
        }
        $this->fragment = $fragment;
        return $this;
    }
    public function url(int $page): string
    {
        $query = array_merge($this->query, [$this->pageName => max(1, $page)]);
        return $this->path . (str_contains($this->path, '?') ? '&' : '?')
            . http_build_query($query, '', '&', PHP_QUERY_RFC3986)
            . ($this->fragment !== null ? '#' . $this->fragment : '');
    }
    public function getUrlRange(int $start, int $end): array
    {
        $urls = [];
        for ($page = $start; $page <= $end; ++$page) {
            $urls[$page] = $this->url($page);
        }
        return $urls;
    }
    public function links(?string $view = null, array $data = []): mixed
    {
        return $this->render($view, $data);
    }
    public function render(?string $view = null, array $data = []): mixed
    {
        if (static::$viewFactoryResolver) {
            return (static::$viewFactoryResolver)()->make($view ?? 'pagination::default', array_merge($data, ['paginator' => $this]));
        }
        if ($view !== null) {
            throw new \LogicException('Register viewFactoryResolver() to use a custom pagination view.');
        }
        if (!$this->hasPages()) {
            return new HtmlString();
        }
        $html = '<nav aria-label="Pagination"><ul class="bluwp-pagination">';
        foreach ($this->linkCollection() as $link) {
            $label = htmlspecialchars($link['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html .= '<li>' . ($link['url'] === null
                ? '<span aria-disabled="true">' . $label . '</span>'
                : '<a href="' . htmlspecialchars($link['url'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"'
                    . ($link['active'] ? ' aria-current="page"' : '') . '>' . $label . '</a>') . '</li>';
        }
        return new HtmlString($html . '</ul></nav>');
    }
    public function linkCollection(): Collection
    {
        return new Collection([
            ['url' => $this->previousPageUrl(), 'label' => 'Previous', 'active' => false],
            ['url' => $this->nextPageUrl(), 'label' => 'Next', 'active' => false],
        ]);
    }
    abstract public function hasMorePages(): bool;
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
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options | JSON_THROW_ON_ERROR);
    }
    public function toHtml(): string
    {
        return (string) $this->render();
    }
    public function __toString(): string
    {
        return $this->toHtml();
    }
}
