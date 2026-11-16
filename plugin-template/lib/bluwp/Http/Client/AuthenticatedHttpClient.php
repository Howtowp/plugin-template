<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\Client;

use Howtowp\Bluwp\Http\Client\Contracts\HttpClient;
use Howtowp\Bluwp\Http\Client\Contracts\RequestAuthenticator;

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
