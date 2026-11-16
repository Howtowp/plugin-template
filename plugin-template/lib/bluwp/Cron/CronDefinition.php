<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Cron;

use Howtowp\Bluwp\Foundation\Application;

interface CronDefinition
{
    public function register(CronScheduler $scheduler, Application $app): void;
}
