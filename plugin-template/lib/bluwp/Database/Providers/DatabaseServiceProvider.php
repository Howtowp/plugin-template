<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Providers;

use Forouzandea\Bluwp\Foundation\ServiceProvider;
use Forouzandea\Bluwp\Foundation\Application;
use Forouzandea\Bluwp\Database\DatabaseManager;
use Forouzandea\Bluwp\Database\DatabaseRegistry;
use Forouzandea\Bluwp\Database\Connection\ConnectionInterface;
use Forouzandea\Bluwp\Database\Schema\Builder;
use Forouzandea\Bluwp\Database\Pagination\WordPressResolvers;

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
