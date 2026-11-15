<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Http\Client\Contracts;

interface AccessTokenCache
{
    public function get(string $key): ?string;

    public function put(string $key, string $token, int $ttl): void;
}
