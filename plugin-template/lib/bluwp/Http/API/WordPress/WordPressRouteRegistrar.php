<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\API\WordPress;

use Howtowp\Bluwp\Http\API\Contracts\RouteRegistrar;
use Howtowp\Bluwp\Http\API\Routing\Route;
use Howtowp\Bluwp\Http\API\Dispatching\EndpointExecutor;
use Howtowp\Bluwp\Http\API\Authorization\PermissionEvaluator;

final class WordPressRouteRegistrar implements RouteRegistrar
{
    public function __construct(private EndpointExecutor $executor, private PermissionEvaluator $permissions)
    {
    }

    /** @param list<Route> $routes */
    public function assertAvailable(array $routes): void
    {
        $existing = rest_get_server()->get_routes();
        foreach ($routes as $route) {
            foreach ($existing as $path => $handlers) {
                $fullPath = '/' . $route->namespace . '/' . trim($route->path, '/');
                if (Route::comparisonPath($path) !== Route::comparisonPath($fullPath)) {
                    continue;
                }
                foreach ($handlers as $handler) {
                    if (is_array($handler) && !empty($handler['methods'][$route->method->value])) {
                        throw new \LogicException(sprintf(__('Duplicate API route: %s.', BLUWP), $route->key()));
                    }
                }
            }
        }
    }

    public function register(Route $route): void
    {
        $registered = register_rest_route($route->namespace, $route->path, [
            'methods' => $route->method->value,
            'callback' => fn (\WP_REST_Request $request) => $this->executor->execute($route->callback, $request),
            'permission_callback' => fn (\WP_REST_Request $request) => $this->permissions->check($route->permission, $request),
            'args' => $route->arguments,
        ]);
        if ($registered === false) {
            throw new \RuntimeException(__('API route registration failed.', BLUWP));
        }
    }
}
