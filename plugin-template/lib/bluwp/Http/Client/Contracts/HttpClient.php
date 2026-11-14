<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Http\Client\Contracts;

use Forouzandea\Bluwp\Http\Client\HttpRequest;
use Forouzandea\Bluwp\Http\Client\HttpResponse;

interface HttpClient
{
    public function send(HttpRequest $request): HttpResponse;
}
