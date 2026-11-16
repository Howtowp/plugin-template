<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\API\Routing;

use Howtowp\Bluwp\Http\API\Dispatching\ControllerDispatcher;
use Howtowp\Bluwp\Http\API\Contracts\Permission;
use Howtowp\Bluwp\Http\HttpMethod;

final class PendingRoute
{
    private array $patterns = [];
    private ?string $routeName = null;
    private bool $sealed = false;

    public function __construct(
        private string $namespace,
        private string $path,
        private HttpMethod $method,
        private string $controller,
        private string $action,
        private Permission $permission
    ) {
        if ($controller === '' || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $action)
            || str_starts_with($action, '__')) {
            throw new \InvalidArgumentException(__('Invalid API route handler.', BLUWP));
        }
    }

    public function name(string $name): self
    {
        $this->assertMutable();
        if ($name === '') {
            throw new \InvalidArgumentException(__('Invalid API route definition.', BLUWP));
        }
        $this->routeName = $name;
        return $this;
    }

    public function where(string $parameter, string $pattern): self
    {
        $this->assertMutable();
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $parameter)
            || !str_contains($this->path, '{' . $parameter . '}') || $pattern === '') {
            throw new \InvalidArgumentException(__('Invalid API route definition.', BLUWP));
        }
        $this->patterns[$parameter] = $pattern;
        return $this;
    }

    public function whereNumber(string $parameter): self
    {
        return $this->where($parameter, '\\d+');
    }

    public function toRoute(ControllerDispatcher $dispatcher): Route
    {
        $parts = preg_split('/(\{[a-zA-Z_][a-zA-Z0-9_]*\})/', $this->path, -1, PREG_SPLIT_DELIM_CAPTURE);
        $path = '';
        $seen = [];
        foreach ($parts as $part) {
            if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/D', $part, $match)) {
                $parameter = $match[1];
                if (isset($seen[$parameter])) {
                    throw new \InvalidArgumentException(__('Invalid API route definition.', BLUWP));
                }
                $seen[$parameter] = true;
                $path .= '(?P<' . $parameter . '>' . ($this->patterns[$parameter] ?? '[^/]+') . ')';
            } else {
                if (str_contains($part, '{') || str_contains($part, '}')) {
                    throw new \InvalidArgumentException(__('Invalid API route definition.', BLUWP));
                }
                $path .= preg_quote($part);
            }
        }
        $controller = $this->controller;
        $action = $this->action;
        return new Route(
            $this->namespace,
            $path ?: '/',
            $this->method,
            static fn (\WP_REST_Request $request) => $dispatcher->dispatch($controller, $action, $request),
            $this->permission,
            name: $this->routeName
        );
    }

    public function routeName(): ?string
    {
        return $this->routeName;
    }

    public function seal(): void
    {
        $this->sealed = true;
    }

    private function assertMutable(): void
    {
        if ($this->sealed) {
            throw new \LogicException(__('API routes are already sealed.', BLUWP));
        }
    }
}
