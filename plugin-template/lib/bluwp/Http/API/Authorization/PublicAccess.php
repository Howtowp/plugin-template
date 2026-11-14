<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Http\API\Authorization;

use Forouzandea\Bluwp\Http\API\Contracts\Permission;

final class PublicAccess implements Permission
{
    public function check(\WP_REST_Request $request): bool
    {
        return true;
    }
}
