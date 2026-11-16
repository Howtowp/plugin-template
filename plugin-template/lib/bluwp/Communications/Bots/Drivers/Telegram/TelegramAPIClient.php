<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\Bots\Drivers\Telegram;

use Howtowp\Bluwp\Communications\Bots\API\AbstractBotAPIClient;
use Howtowp\Bluwp\Http\Client\Validation\PublicHttpsUrl;

final class TelegramAPIClient extends AbstractBotAPIClient
{
    public const DEFAULT_BASE_URL = 'https://api.telegram.org';

    public static function normalizeBaseUrl(string $url): string
    {
        $url = rtrim(trim($url), '/');
        if ($url === '') {
            return self::DEFAULT_BASE_URL;
        }
        PublicHttpsUrl::assertValid($url);
        $path = parse_url($url, PHP_URL_PATH);
        if (!in_array($path, [null, '', '/', '/bot'], true) || parse_url($url, PHP_URL_QUERY) !== null) {
            throw new \DomainException(__('The Telegram API base URL must use public HTTPS.', BLUWP));
        }
        return preg_replace('~/bot$~', '', $url);
    }

    protected function resolveBaseUrl(string $baseUrl): string
    {
        return self::normalizeBaseUrl($baseUrl);
    }
}
