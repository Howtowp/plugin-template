<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Http\API\Routing;

use Forouzandea\Bluwp\Http\API\Dispatching\ControllerDispatcher;

final class RouteCollection
{
    /** @var list<PendingRoute> */
    private array $routes = [];
    private bool $registered = false;
    private int $groupDepth = 0;

    public function add(PendingRoute $route): void
    {
        if ($this->registered) {
            throw new \LogicException(__('API routes are already sealed.', BLUWP));
        }
        $this->routes[] = $route;
    }

    public function transaction(callable $callback): void
    {
        $snapshot = $this->routes;
        $this->groupDepth++;
        try {
            $callback();
        } catch (\Throwable $error) {
            $this->routes = $snapshot;
            throw $error;
        } finally {
            $this->groupDepth--;
        }
    }

    public function register(RouteRegistry $registry, ControllerDispatcher $dispatcher): void
    {
        if ($this->groupDepth !== 0) {
            throw new \LogicException(__('API routes cannot be published during collection.', BLUWP));
        }
        if ($this->registered) {
            return;
        }
        $compiled = [];
        foreach ($this->routes as $pending) {
            $compiled[] = $pending->toRoute($dispatcher);
        }
        $registry->addBatch($compiled);
        foreach ($this->routes as $route) {
            $route->seal();
        }
        $this->registered = true;
    }
}
