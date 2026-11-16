<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\API\Authorization;

use Howtowp\Bluwp\Http\API\Contracts\Permission;

final class Capability implements Permission
{
    public function __construct(private string $capability)
    {
        if (trim($capability) === '') {
            throw new \InvalidArgumentException(__('Invalid API permission configuration.', BLUWP));
        }
    }

    public function check(\WP_REST_Request $request): bool|\WP_Error
    {
        return current_user_can($this->capability)
            ? true
            : new \WP_Error('bluwp_api_forbidden', __('API access denied.', BLUWP), ['status' => 403]);
    }
}
