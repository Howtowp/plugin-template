<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Foundation\Bootstrap;

use Forouzandea\Bluwp\Foundation\Application;
use Forouzandea\Bluwp\Hooks\HooksServiceProvider;
use Forouzandea\Bluwp\Http\API\APIServiceProvider;
use Forouzandea\Bluwp\Admin\AdminServiceProvider;
use Forouzandea\Bluwp\Cron\CronServiceProvider;
use Forouzandea\Bluwp\Database\Providers\DatabaseServiceProvider;
use Forouzandea\Bluwp\Database\Providers\MigrationServiceProvider;

final class DefaultProviders
{
    /** @return list<class-string<\Forouzandea\Bluwp\Foundation\ServiceProvider>> */
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
