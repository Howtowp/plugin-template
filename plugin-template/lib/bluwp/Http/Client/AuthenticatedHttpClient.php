<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Http\Client;

use Forouzandea\Bluwp\Http\Client\Contracts\HttpClient;
use Forouzandea\Bluwp\Http\Client\Contracts\RequestAuthenticator;

final class AuthenticatedHttpClient implements HttpClient
{
    public function __construct(private HttpClient $client, private RequestAuthenticator $authenticator)
    {
    }

    public function send(HttpRequest $request): HttpResponse
    {
        return $this->client->send($this->authenticator->authenticate($request));
    }
}
