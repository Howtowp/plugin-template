<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database\Eloquent\Relations;

final class Pivot implements \ArrayAccess, \JsonSerializable, \Howtowp\Bluwp\Contracts\Arrayable
{
    public function __construct(private array $attributes)
    {
    }
    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }
    public function toArray(): array
    {
        return $this->attributes;
    }
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }
    public function offsetGet(mixed $offset): mixed
    {
        return $this->attributes[$offset] ?? null;
    }
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('Use updateExistingPivot() to persist pivot changes.');
    }
    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('Use detach() to remove pivot records.');
    }
}
