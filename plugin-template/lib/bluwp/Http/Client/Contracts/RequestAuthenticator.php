<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\Client\Contracts;

use Howtowp\Bluwp\Http\Client\HttpRequest;

interface RequestAuthenticator
{
    public function authenticate(HttpRequest $request): HttpRequest;
}
