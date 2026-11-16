<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\API\Contracts;

use Howtowp\Bluwp\Http\API\Authentication\AuthContext;

interface Permission
{
    /**
     * Return true or an authenticated identity on success, false or WP_Error on denial.
     * Policies must not mutate request identity; exceptions indicate internal failures.
     */
    public function check(\WP_REST_Request $request): bool|AuthContext|\WP_Error;
}
