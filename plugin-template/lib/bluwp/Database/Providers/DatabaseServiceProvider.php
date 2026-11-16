<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database\Providers;

use Howtowp\Bluwp\Foundation\ServiceProvider;
use Howtowp\Bluwp\Foundation\Application;
use Howtowp\Bluwp\Database\DatabaseManager;
use Howtowp\Bluwp\Database\DatabaseRegistry;
use Howtowp\Bluwp\Database\Connection\ConnectionInterface;
use Howtowp\Bluwp\Database\Schema\Builder;
use Howtowp\Bluwp\Database\Pagination\WordPressResolvers;

final class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(ConnectionInterface::class, static fn () => DatabaseRegistry::wordpressAdapter());
        $this->singleton(DatabaseManager::class, static fn (Application $app) => new DatabaseManager($app->get(ConnectionInterface::class)));
        $this->singleton(Builder::class, static fn (Application $app) => $app->get(DatabaseManager::class)->connection('wordpress')->getSchemaBuilder());
        $app = $this->app;
        DatabaseRegistry::register(
            (string) $app->config('name'),
            (string) $app->config('namespace'),
            static fn () => $app->get(DatabaseManager::class)
        );
        WordPressResolvers::register();
    }
}
