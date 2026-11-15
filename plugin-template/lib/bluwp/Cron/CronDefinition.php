<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Cron;

use Forouzandea\Bluwp\Foundation\Application;

interface CronDefinition
{
    public function register(CronScheduler $scheduler, Application $app): void;
}
