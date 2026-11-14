<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Providers;

use Forouzandea\Bluwp\Foundation\ServiceProvider;
use Forouzandea\Bluwp\Foundation\Application;
use Forouzandea\Bluwp\Database\Connection\ConnectionInterface;
use Forouzandea\Bluwp\Database\Migrations\MigrationRepository;
use Forouzandea\Bluwp\Database\Migrations\Migrator;
use Forouzandea\Bluwp\Database\Migrations\AutomaticMigrator;
use Forouzandea\Bluwp\Support\Diagnostics\DiagnosticReporter;
use Forouzandea\Bluwp\Support\Diagnostics\PhpDiagnosticReporter;

final class MigrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(DiagnosticReporter::class, static fn () => new PhpDiagnosticReporter());
        $this->singleton(Migrator::class, static fn (Application $app) => new Migrator(
            new MigrationRepository($app->get(ConnectionInterface::class), (string) $app->config('name'))
        ));
        $path = (string) $this->app->config('path', '');
        if ($path !== '') {
            $this->singleton(AutomaticMigrator::class, static fn (Application $app) => new AutomaticMigrator(
                $app->get(Migrator::class),
                (string) $app->config('name'),
                (string) $app->config('version'),
                (string) $app->config('migrationsPath', rtrim($path, '/\\') . '/database/migrations'),
                $app->get(DiagnosticReporter::class)
            ));
        }
    }

    public function boot(): void
    {
        if (!$this->app->has(AutomaticMigrator::class)) {
            return;
        }
        $check = function (): void {
            $scope = (string) $this->app->config('name');
            $version = (string) $this->app->config('version');
            if (get_option($scope . '_migration_version', null) !== $version) {
                $this->app->get(AutomaticMigrator::class)->maybeRun();
            }
        };
        // Plugin boot can run inside init: earlier priorities will not run retroactively.
        if (function_exists('did_action') && did_action('init') > 0) {
            $check();
        } else {
            add_action('init', $check, -1000);
        }
    }
}
