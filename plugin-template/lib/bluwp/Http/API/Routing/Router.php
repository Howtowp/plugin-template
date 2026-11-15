<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Http\API\Routing;

use Forouzandea\Bluwp\Http\API\Dispatching\ControllerDispatcher;
use Forouzandea\Bluwp\Http\API\Contracts\Permission;
use Forouzandea\Bluwp\Http\API\Authorization\AllOf;
use Forouzandea\Bluwp\Http\API\Authorization\Capability;
use Forouzandea\Bluwp\Http\API\Authorization\LazyPermission;
use Forouzandea\Bluwp\Http\API\Authorization\PublicAccess;
use Forouzandea\Bluwp\Foundation\Application;
use Forouzandea\Bluwp\Http\HttpMethod;

final class Router
{
    private RouteCollection $collection;
    private string $namespace = '';
    private string $prefix = '';
    private string $controller = '';
    private ?Permission $permission = null;
    private array $loadedFiles = [];

    public function __construct(private Application $app, private RouteRegistry $registry, private ControllerDispatcher $dispatcher)
    {
        $this->collection = new RouteCollection();
    }

    public function namespace(string $namespace): self
    {
        $group = clone $this;
        $group->namespace = $namespace;
        return $group;
    }

    public function prefix(string $prefix): self
    {
        $group = clone $this;
        $group->prefix = rtrim($this->prefix, '/') . '/' . trim($prefix, '/');
        return $group;
    }

    public function controller(string $controller): self
    {
        $group = clone $this;
        $group->controller = $controller;
        return $group;
    }

    public function permission(string|Permission $permission): self
    {
        $group = clone $this;
        $policy = is_string($permission) ? new LazyPermission($this->app, $permission) : $permission;
        $group->permission = $this->permission === null ? $policy : new AllOf($this->permission, $policy);
        return $group;
    }

    public function capability(string $capability): self
    {
        return $this->permission(new Capability($capability));
    }

    public function publicAccess(): self
    {
        return $this->permission(new PublicAccess());
    }

    public function group(callable $definition): void
    {
        $this->collection->transaction(fn () => $definition($this));
    }

    public function load(string ...$routes): void
    {
        foreach ($routes as $route) {
            $path = $this->resolveRoutePath($route);
            $resolved = realpath($path);
            if ($resolved !== false && isset($this->loadedFiles[$resolved])) {
                continue;
            }
            if (!is_file($path) || !is_readable($path)) {
                throw new \RuntimeException(__('API route file is missing or unreadable.', BLUWP));
            }
            $definition = (static fn (string $file) => require $file)($path);
            if (!$definition instanceof \Closure) {
                throw new \UnexpectedValueException(__('API route file must return a closure.', BLUWP));
            }
            $this->group($definition);
            $this->loadedFiles[$resolved] = true;
        }
    }

    public function get(string $path, string $action): PendingRoute
    {
        return $this->add(HttpMethod::Get, $path, $action);
    }
    public function post(string $path, string $action): PendingRoute
    {
        return $this->add(HttpMethod::Post, $path, $action);
    }
    public function put(string $path, string $action): PendingRoute
    {
        return $this->add(HttpMethod::Put, $path, $action);
    }
    public function patch(string $path, string $action): PendingRoute
    {
        return $this->add(HttpMethod::Patch, $path, $action);
    }
    public function delete(string $path, string $action): PendingRoute
    {
        return $this->add(HttpMethod::Delete, $path, $action);
    }

    public function register(): void
    {
        $this->collection->register($this->registry, $this->dispatcher);
    }

    private function resolveRoutePath(string $route): string
    {
        if (str_contains($route, '/') || str_contains($route, '\\')) {
            return $route;
        }
        $root = rtrim((string) $this->app->config('path'), '/\\');
        return $root . '/routes/' . $route . (str_ends_with($route, '.php') ? '' : '.php');
    }

    private function add(HttpMethod $method, string $path, string $action): PendingRoute
    {
        if ($this->permission === null) {
            throw new \LogicException(__('API route requires an explicit permission.', BLUWP));
        }
        $route = new PendingRoute(
            $this->namespace,
            rtrim($this->prefix, '/') . ($path === '' ? '' : '/' . ltrim($path, '/')) ?: '/',
            $method,
            $this->controller,
            $action,
            $this->permission
        );
        $this->collection->add($route);
        return $route;
    }
}
