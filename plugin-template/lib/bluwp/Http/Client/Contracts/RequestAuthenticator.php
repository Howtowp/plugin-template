<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Http\Client\Contracts;

use Forouzandea\Bluwp\Http\Client\HttpRequest;

interface RequestAuthenticator
{
    public function authenticate(HttpRequest $request): HttpRequest;
}
