<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\Bots\Drivers\Bale;

use Howtowp\Bluwp\Communications\Bots\API\AbstractBotAPIClient;

final class BaleAPIClient extends AbstractBotAPIClient
{
    protected function resolveBaseUrl(string $baseUrl): string
    {
        return 'https://tapi.bale.ai';
    }
}
