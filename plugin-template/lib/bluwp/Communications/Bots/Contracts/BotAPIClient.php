<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\Bots\Contracts;

interface BotAPIClient
{
    public function call(string $token, string $method, array $parameters = [], string $baseUrl = ''): mixed;
}
