<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Foundation\Bootstrap;

use Howtowp\Bluwp\Foundation\Application;
use Howtowp\Bluwp\Hooks\HooksServiceProvider;
use Howtowp\Bluwp\Http\API\APIServiceProvider;
use Howtowp\Bluwp\Admin\AdminServiceProvider;
use Howtowp\Bluwp\Cron\CronServiceProvider;
use Howtowp\Bluwp\Database\Providers\DatabaseServiceProvider;
use Howtowp\Bluwp\Database\Providers\MigrationServiceProvider;

final class DefaultProviders
{
    /** @return list<class-string<\Howtowp\Bluwp\Foundation\ServiceProvider>> */
    public function forApplication(Application $app): array
    {
        $features = $app->config('features', []);
        $providers = [];
        if ($features['database'] ?? true) {
            $providers[] = DatabaseServiceProvider::class;
            if ($features['migrations'] ?? true) {
                $providers[] = MigrationServiceProvider::class;
            }
        } elseif ($features['migrations'] ?? false) {
            throw new \LogicException('Migrations require the database feature.');
        }
        if ($features['cron'] ?? true) {
            $providers[] = CronServiceProvider::class;
        }
        if ($features['api'] ?? true) {
            $providers[] = APIServiceProvider::class;
        }
        if ($features['admin'] ?? ($app->config('admin') !== null)) {
            $providers[] = AdminServiceProvider::class;
        }
        if ($features['hooks'] ?? true) {
            $providers[] = HooksServiceProvider::class;
        }
        return $providers;
    }
}
