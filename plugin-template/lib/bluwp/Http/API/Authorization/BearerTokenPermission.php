<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\API\Authorization;

use Howtowp\Bluwp\Http\API\Authentication\AuthContext;
use Howtowp\Bluwp\Http\API\Authentication\BearerTokenAuthenticator;
use Howtowp\Bluwp\Http\API\Contracts\Permission;

final class BearerTokenPermission implements Permission
{
    /** @param list<string> $requiredScopes */
    public function __construct(private BearerTokenAuthenticator $authenticator, private array $requiredScopes = [])
    {
        if (array_filter($requiredScopes, static fn ($scope): bool => !is_string($scope) || trim($scope) === '') !== []) {
            throw new \InvalidArgumentException(__('Invalid API permission configuration.', BLUWP));
        }
    }

    public function check(\WP_REST_Request $request): AuthContext|\WP_Error
    {
        $context = $this->authenticator->authenticate($request);
        if ($context instanceof \WP_Error) {
            return $context;
        }
        foreach ($this->requiredScopes as $scope) {
            if (!$context->allows($scope)) {
                return new \WP_Error('bluwp_api_forbidden', __('API access denied.', BLUWP), ['status' => 403]);
            }
        }
        return $context;
    }
}
