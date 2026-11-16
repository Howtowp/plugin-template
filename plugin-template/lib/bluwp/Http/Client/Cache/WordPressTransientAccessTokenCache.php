<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\Client\Cache;

use Howtowp\Bluwp\Http\Client\Contracts\AccessTokenCache;

final class WordPressTransientAccessTokenCache implements AccessTokenCache
{
    public function get(string $key): ?string
    {
        $value = get_transient($key);
        return is_string($value) && $value !== '' ? $value : null;
    }

    public function put(string $key, string $token, int $ttl): void
    {
        if (!set_transient($key, $token, $ttl)) {
            throw new \RuntimeException(__('The OAuth access token could not be cached.', BLUWP));
        }
    }
}
