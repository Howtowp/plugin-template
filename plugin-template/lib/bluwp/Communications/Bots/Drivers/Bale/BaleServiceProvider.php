<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\Bots\Drivers\Bale;

use Forouzandea\Bluwp\Foundation\Application;
use Forouzandea\Bluwp\Foundation\ServiceProvider;
use Forouzandea\Bluwp\Foundation\DeferrableProvider;
use Forouzandea\Bluwp\Http\Client\Contracts\HttpClient;
use Forouzandea\Bluwp\Http\Client\WordPressHttpClient;

final class BaleServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public static function provides(): array
    {
        return [BaleAPIClient::class, BaleWebhookManager::class];
    }

    public function register(): void
    {
        $this->singleton(HttpClient::class, static fn () => new WordPressHttpClient());
        $this->singleton(BaleAPIClient::class, static fn (Application $app) =>
            new BaleAPIClient($app->get(HttpClient::class)));
        $this->singleton(BaleWebhookManager::class, static fn (Application $app) =>
            new BaleWebhookManager($app->get(BaleAPIClient::class)));
    }
}
