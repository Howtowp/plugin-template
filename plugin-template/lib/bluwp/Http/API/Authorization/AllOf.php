<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\API\Authorization;

use Howtowp\Bluwp\Http\API\Contracts\Permission;
use Howtowp\Bluwp\Http\API\Authentication\AuthContext;

final class AllOf implements Permission
{
    private array $permissions;

    public function __construct(Permission $first, Permission ...$others)
    {
        $this->permissions = [$first, ...$others];
    }

    public function check(\WP_REST_Request $request): bool|AuthContext|\WP_Error
    {
        $identity = null;
        foreach ($this->permissions as $permission) {
            $result = $permission->check($request);
            if ($result instanceof AuthContext) {
                if ($identity !== null && $identity !== $result) {
                    if ($identity->subject !== $result->subject || $identity->authority !== $result->authority) {
                        return new \WP_Error('bluwp_api_identity_conflict', __('API access denied.', BLUWP), ['status' => 403]);
                    }
                    // Never expand authority by combining independently returned scopes.
                    $result = new AuthContext($identity->subject, $identity->authority, array_values(array_intersect($identity->scopes, $result->scopes)));
                }
                $identity = $result;
            } elseif ($result !== true) {
                return $result;
            }
        }

        return $identity ?? true;
    }
}
