<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support;

final class LazyCollection implements \IteratorAggregate
{
    public function __construct(private \Closure $source)
    {
    }
    public function getIterator(): \Traversable
    {
        yield from ($this->source)();
    }
    public function map(callable $callback): self
    {
        return new self(function () use ($callback) {
            foreach ($this as $key => $item) {
                yield $key => $callback($item, $key);
            }
        });
    }
    public function filter(?callable $callback = null): self
    {
        return new self(function () use ($callback) {
            foreach ($this as $key => $item) {
                if ($callback ? $callback($item, $key) : $item) {
                    yield $key => $item;
                }
            }
        });
    }
    public function take(int $count): self
    {
        if ($count < 0) {
            throw new \InvalidArgumentException('Lazy take requires a nonnegative count.');
        }
        return new self(function () use ($count) {
            if ($count === 0) {
                return;
            } $seen = 0;
            foreach ($this as $key => $item) {
                yield $key => $item;
                if (++$seen >= $count) {
                    break;
                }
            }
        });
    }
    public function each(callable $callback): static
    {
        foreach ($this as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        } return $this;
    }
    public function all(): array
    {
        return iterator_to_array($this);
    }
    public function collect(): Collection
    {
        return new Collection($this);
    }
    public function count(): int
    {
        return iterator_count($this->getIterator());
    }
}
