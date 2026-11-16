<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\API\Authorization;

use Howtowp\Bluwp\Http\API\Authentication\AuthContext;
use Howtowp\Bluwp\Http\API\Authentication\AuthContextStore;
use Howtowp\Bluwp\Http\API\Contracts\ErrorReporter;
use Howtowp\Bluwp\Http\API\Contracts\Permission;

/** Commits identity only after the complete policy succeeds. */
final class PermissionEvaluator
{
    public function __construct(private AuthContextStore $contexts, private ErrorReporter $reporter)
    {
    }

    public function check(Permission $permission, \WP_REST_Request $request): bool|\WP_Error
    {
        $this->contexts->forget($request);
        try {
            $result = $permission->check($request);
        } catch (\Throwable $error) {
            $this->reporter->report($error);
            return new \WP_Error('bluwp_api_error', __('The request could not be completed.', BLUWP), ['status' => 500]);
        }
        if ($result instanceof AuthContext) {
            $this->contexts->put($request, $result);
            return true;
        }
        return $result === true ? true : ($result instanceof \WP_Error ? $result
            : new \WP_Error('bluwp_api_forbidden', __('API access denied.', BLUWP), ['status' => 403]));
    }
}
