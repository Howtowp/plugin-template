<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\API\Authorization;

use Howtowp\Bluwp\Http\API\Contracts\Permission;
use Howtowp\Bluwp\Http\API\Authentication\AuthContext;

final class AnyOf implements Permission
{
    private array $permissions;

    public function __construct(Permission $first, Permission ...$others)
    {
        $this->permissions = [$first, ...$others];
    }

    public function check(\WP_REST_Request $request): bool|AuthContext|\WP_Error
    {
        $failure = null;
        foreach ($this->permissions as $permission) {
            $result = $permission->check($request);
            if ($result === true || $result instanceof AuthContext) {
                return $result;
            }
            if ($result instanceof \WP_Error && ($failure === null || $this->priority($result) > $this->priority($failure))) {
                $failure = $result;
            }
        }

        return $failure ?? new \WP_Error('bluwp_api_forbidden', __('API access denied.', BLUWP), ['status' => 403]);
    }

    /** Operational failures take precedence, then missing credentials, then denials. */
    private function priority(\WP_Error $error): int
    {
        $data = $error->get_error_data();
        $status = is_array($data) ? ($data['status'] ?? 403) : 403;
        return $status >= 500 ? 3 : ($status === 401 ? 2 : 1);
    }
}
