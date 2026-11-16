<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\Client\Authentication;

use Howtowp\Bluwp\Http\Client\Contracts\RequestAuthenticator;
use Howtowp\Bluwp\Http\Client\HttpRequest;

final class BearerAuthentication implements RequestAuthenticator
{
    public function __construct(private string $token)
    {
        if ($token === '' || preg_match('/[\x00-\x20\x7F]/', $token)) {
            throw new \InvalidArgumentException(__('Invalid HTTP bearer token.', BLUWP));
        }
    }

    public function authenticate(HttpRequest $request): HttpRequest
    {
        return $request->withHeaders(['Authorization' => 'Bearer ' . $this->token]);
    }
}
