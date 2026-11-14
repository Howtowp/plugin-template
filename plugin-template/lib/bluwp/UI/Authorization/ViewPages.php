<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\UI\Authorization;

use Forouzandea\Bluwp\UI\Pages\PageRegistry;
use Forouzandea\Bluwp\Http\API\Contracts\Permission;
use WP_Error;
use WP_REST_Request;

final class ViewPages implements Permission
{
    public function __construct(private PageRegistry $pages)
    {
    }

    public function check(WP_REST_Request $request): bool|WP_Error
    {
        return is_user_logged_in() && $this->pages->visible() !== []
            ? true
            : new WP_Error('bluwp_api_forbidden', __('API access denied.', BLUWP), ['status' => 403]);
    }
}
