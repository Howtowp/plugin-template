<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Http\API\Dispatching;

use Forouzandea\Bluwp\Http\API\Controller;
use Forouzandea\Bluwp\Foundation\Application;

final class ControllerDispatcher
{
    public function __construct(private Application $app)
    {
    }

    public function dispatch(string $controller, string $action, \WP_REST_Request $request): mixed
    {
        $instance = $this->app->get($controller);
        if (!$instance instanceof Controller || !method_exists($instance, $action)) {
            throw new \LogicException(__('Invalid API route handler.', BLUWP));
        }
        $method = new \ReflectionMethod($instance, $action);
        if (!$method->isPublic() || $method->isStatic() || str_starts_with($action, '__')) {
            throw new \LogicException(__('Invalid API route handler.', BLUWP));
        }
        return $instance->{$action}($request);
    }
}
