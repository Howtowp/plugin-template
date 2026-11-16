<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\API\Authorization;

use Howtowp\Bluwp\Http\API\Contracts\Permission;

final class PublicAccess implements Permission
{
    public function check(\WP_REST_Request $request): bool
    {
        return true;
    }
}
