<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\Bots\Drivers\Telegram;

use Forouzandea\Bluwp\Foundation\Application;
use Forouzandea\Bluwp\Foundation\ServiceProvider;
use Forouzandea\Bluwp\Foundation\DeferrableProvider;
use Forouzandea\Bluwp\Http\Client\Contracts\HttpClient;
use Forouzandea\Bluwp\Http\Client\WordPressHttpClient;

final class TelegramServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public static function provides(): array
    {
        return [TelegramAPIClient::class, TelegramWebhookManager::class];
    }

    public function register(): void
    {
        $this->singleton(HttpClient::class, static fn () => new WordPressHttpClient());
        $this->singleton(TelegramAPIClient::class, static fn (Application $app) =>
            new TelegramAPIClient($app->get(HttpClient::class)));
        $this->singleton(TelegramWebhookManager::class, static fn (Application $app) =>
            new TelegramWebhookManager($app->get(TelegramAPIClient::class)));
    }
}
