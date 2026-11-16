<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\API\Authentication;

use Howtowp\Bluwp\Http\API\Contracts\ErrorReporter;
use Howtowp\Bluwp\Http\API\Contracts\TokenValidator;

final class BearerTokenAuthenticator
{
    public function __construct(private TokenValidator $validator, private ErrorReporter $reporter)
    {
    }

    public function authenticate(\WP_REST_Request $request): AuthContext|\WP_Error
    {
        $header = $request->get_header('authorization');
        if (!is_string($header) || strlen($header) > 4103
            || !preg_match('/^Bearer ([A-Za-z0-9._~+\/-]+=*)$/iD', $header, $matches)) {
            return $this->unauthorized();
        }
        try {
            $context = $this->validator->validate($matches[1], $request);
        } catch (\Throwable $error) {
            $this->reporter->report($error);
            return new \WP_Error('bluwp_api_auth_unavailable', __('API authentication is unavailable.', BLUWP), ['status' => 503]);
        }
        return $context instanceof \WP_Error ? $this->unauthorized() : $context;
    }

    private function unauthorized(): \WP_Error
    {
        return new \WP_Error('bluwp_api_unauthorized', __('A valid Bearer token is required.', BLUWP), ['status' => 401]);
    }
}
