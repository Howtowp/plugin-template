<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\SMS\Providers;

use Howtowp\Bluwp\Communications\SMS\Sending\SMSManager;
use Howtowp\Bluwp\Communications\SMS\Gateways\GatewayRegistry;
use Howtowp\Bluwp\Foundation\Application;
use Howtowp\Bluwp\Foundation\ServiceProvider;
use Howtowp\Bluwp\Foundation\DeferrableProvider;
use Howtowp\Bluwp\Http\Client\Contracts\HttpClient;
use Howtowp\Bluwp\Http\Client\WordPressHttpClient;

final class SMSServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public static function provides(): array
    {
        return [GatewayRegistry::class, SMSManager::class];
    }

    public function register(): void
    {
        $this->singleton(HttpClient::class, static fn () => new WordPressHttpClient());
        $this->singleton(GatewayRegistry::class, static fn () => GatewayRegistry::defaults());
        $this->singleton(SMSManager::class, static fn (Application $app) =>
            new SMSManager($app->get(GatewayRegistry::class), $app->get(HttpClient::class)));
    }
}
