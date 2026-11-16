<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\Client\Authentication;

use Howtowp\Bluwp\Http\Client\Contracts\RequestAuthenticator;
use Howtowp\Bluwp\Http\Client\HttpRequest;

final class BasicAuthentication implements RequestAuthenticator
{
    public function __construct(private string $username, private string $password)
    {
        if ($username === '' || preg_match('/[\x00-\x1F\x7F:]/', $username)
            || preg_match('/[\x00-\x1F\x7F]/', $password)) {
            throw new \InvalidArgumentException(__('Invalid HTTP Basic authentication credentials.', BLUWP));
        }
    }

    public function authenticate(HttpRequest $request): HttpRequest
    {
        return $request->withHeaders([
            'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->password),
        ]);
    }
}
