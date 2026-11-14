<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Http\API\Routing;

use Forouzandea\Bluwp\Http\API\Contracts\RouteRegistrar;

final class RouteRegistry
{
    /** @var array<string, Route> */
    private array $routes = [];
    /** @var array<string, Route> */
    private array $names = [];
    private array $published = [];
    private bool $sealed = false;

    public function __construct(private RouteRegistrar $registrar)
    {
    }

    public function add(Route $route): self
    {
        $this->addBatch([$route]);
        return $this;
    }

    /** Validate a complete batch before changing the registry. @param list<Route> $routes */
    public function addBatch(array $routes): void
    {
        if ($this->sealed) {
            throw new \LogicException(__('API routes are already sealed.', BLUWP));
        }
        $definitions = $this->routes;
        $names = $this->names;
        foreach ($routes as $route) {
            $key = $route->method->value . ' ' . Route::comparisonPath('/' . $route->namespace . '/' . trim($route->path, '/'));
            if (isset($definitions[$key]) || ($route->name !== null && isset($names[$route->name]))) {
                throw new \LogicException(sprintf(__('Duplicate API route: %s.', BLUWP), $route->name ?? $key));
            }
            $definitions[$key] = $route;
            if ($route->name !== null) {
                $names[$route->name] = $route;
            }
        }
        $this->routes = $definitions;
        $this->names = $names;
    }

    public function named(string $name): ?Route
    {
        return $this->names[$name] ?? null;
    }

    /** @return list<Route> */
    public function all(): array
    {
        return array_values($this->routes);
    }

    public function register(): void
    {
        $pending = array_diff_key($this->routes, $this->published);
        $this->registrar->assertAvailable(array_values($pending));
        $this->sealed = true;
        foreach ($pending as $key => $route) {
            $this->registrar->register($route);
            $this->published[$key] = true;
        }
    }
}
