<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Http\API\Contracts;

use Forouzandea\Bluwp\Http\API\Routing\Route;

interface RouteRegistrar
{
    /** @param list<Route> $routes */
    public function assertAvailable(array $routes): void;

    public function register(Route $route): void;
}
