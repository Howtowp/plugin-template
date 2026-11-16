<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\Client\Contracts;

use Howtowp\Bluwp\Http\Client\HttpRequest;
use Howtowp\Bluwp\Http\Client\HttpResponse;

interface HttpClient
{
    public function send(HttpRequest $request): HttpResponse;
}
