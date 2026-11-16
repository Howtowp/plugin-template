<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\API\Contracts;

use Howtowp\Bluwp\Http\API\Authentication\AuthContext;

interface TokenValidator
{
    /** Verify signature, expiry, revocation, issuer and audience; return a context with a trusted authority identifier. */
    public function validate(string $token, \WP_REST_Request $request): AuthContext|\WP_Error;
}
