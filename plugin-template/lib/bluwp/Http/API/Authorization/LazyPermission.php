<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Http\API\Authorization;

use Forouzandea\Bluwp\Http\API\Contracts\Permission;
use Forouzandea\Bluwp\Http\API\Authentication\AuthContext;
use Forouzandea\Bluwp\Foundation\Application;

final class LazyPermission implements Permission
{
    public function __construct(private Application $app, private string $service)
    {
    }

    public function check(\WP_REST_Request $request): bool|AuthContext|\WP_Error
    {
        $permission = $this->app->get($this->service);
        if (!$permission instanceof Permission) {
            throw new \LogicException(__('Invalid API permission service.', BLUWP));
        }
        return $permission->check($request);
    }
}
