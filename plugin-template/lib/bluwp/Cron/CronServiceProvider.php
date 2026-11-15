<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Cron;

use Forouzandea\Bluwp\Foundation\Application;
use Forouzandea\Bluwp\Foundation\ServiceProvider;

final class CronServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(CronScheduler::class, static fn (Application $app) =>
            new CronScheduler((string) $app->config('name')));
    }

    public function boot(): void
    {
        $scheduler = $this->app->get(CronScheduler::class);
        foreach ([
            CronScheduler::SIX_TIMES_PER_MINUTE => __('6 times per minute', 'bluwp'),
            CronScheduler::THREE_TIMES_PER_MINUTE => __('3 times per minute', 'bluwp'),
            CronScheduler::ONCE_PER_MINUTE => __('Once per minute', 'bluwp'),
            CronScheduler::TWELVE_TIMES_PER_HOUR => __('12 times per hour', 'bluwp'),
            CronScheduler::SIX_TIMES_PER_HOUR => __('6 times per hour', 'bluwp'),
            CronScheduler::FOUR_TIMES_PER_HOUR => __('4 times per hour', 'bluwp'),
            CronScheduler::TWICE_PER_HOUR => __('Twice per hour', 'bluwp'),
            CronScheduler::ONCE_PER_HOUR => __('Once per hour', 'bluwp'),
            CronScheduler::SIX_TIMES_PER_DAY => __('6 times per day', 'bluwp'),
            CronScheduler::THREE_TIMES_PER_DAY => __('3 times per day', 'bluwp'),
            CronScheduler::TWICE_PER_DAY => __('Twice per day', 'bluwp'),
            CronScheduler::ONCE_PER_DAY => __('Once per day', 'bluwp'),
            CronScheduler::TWICE_PER_WEEK => __('Twice per week', 'bluwp'),
            CronScheduler::ONCE_PER_WEEK => __('Once per week', 'bluwp'),
            CronScheduler::THREE_TIMES_PER_MONTH => __('3 times per month (every 10 days)', 'bluwp'),
            CronScheduler::TWICE_PER_MONTH => __('Twice per month (every 15 days)', 'bluwp'),
            CronScheduler::ONCE_PER_MONTH => __('Once per month (30 days)', 'bluwp'),
        ] as $seconds => $label) {
            $scheduler->interval($seconds, $label);
        }
        $path = (string) $this->app->config('path', '');
        if ($path !== '') {
            (new CronLoader())->load(rtrim($path, '/\\') . '/crons', $scheduler, $this->app);
        }
    }
}
