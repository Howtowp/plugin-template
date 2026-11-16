<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Container;

class Container
{
    private array $services = [];
    private array $factories = [];
    private array $resolving = [];

    public function has(string $key): bool
    {
        return $this->bound($key);
    }

    /** Whether a concrete service or factory is already registered. */
    public function bound(string $key): bool
    {
        return array_key_exists($key, $this->services) || isset($this->factories[$key]);
    }

    public function set(string $key, mixed $service): void
    {
        if ($this->bound($key)) {
            throw new \LogicException('Service already registered: ' . $key);
        }
        $this->services[$key] = $service;
    }

    public function singleton(string $key, callable $factory): void
    {
        if ($this->bound($key)) {
            throw new \LogicException('Service already registered: ' . $key);
        }
        $this->factories[$key] = $factory;
    }

    /** Remove a binding; retained for container subclasses. */
    protected function removeBinding(string $key): void
    {
        unset($this->services[$key], $this->factories[$key]);
    }

    /** Roll back binding maps and resolved-instance caches on failure, including nested registrations. */
    protected function withBindingTransaction(callable $operation): void
    {
        $state = [$this->services, $this->factories, $this->resolving];
        try {
            $operation();
        } catch (\Throwable $error) {
            [$this->services, $this->factories, $this->resolving] = $state;
            throw $error;
        }
    }

    public function get(string $key): mixed
    {
        if (array_key_exists($key, $this->services)) {
            return $this->services[$key];
        }
        if (isset($this->resolving[$key])) {
            throw new \LogicException('Circular service dependency: ' . $key);
        }
        $this->resolving[$key] = true;
        try {
            if (isset($this->factories[$key])) {
                return $this->services[$key] = ($this->factories[$key])($this);
            }
            return $this->services[$key] = (new AutowireResolver())->resolve($key, $this);
        } finally {
            unset($this->resolving[$key]);
        }
    }

}
