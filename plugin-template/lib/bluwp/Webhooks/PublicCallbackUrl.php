<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Webhooks;

use Forouzandea\Bluwp\Http\Client\Validation\PublicHttpsUrl;

final class PublicCallbackUrl
{
    public static function assertValid(string $url, array $allowedPorts = [443]): void
    {
        PublicHttpsUrl::assertValid($url, $allowedPorts);
    }
}
