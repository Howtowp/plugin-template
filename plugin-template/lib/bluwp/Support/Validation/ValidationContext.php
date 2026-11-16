<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation;

final class ValidationContext
{
    public function __construct(private array $data)
    {
    }

    public function has(string $path): bool
    {
        return DataPath::has($this->data, $path);
    }

    public function get(string $path, mixed $default = null): mixed
    {
        return DataPath::get($this->data, $path, $default);
    }

    public function data(): array
    {
        return $this->data;
    }
}
