<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Hooks;

use Howtowp\Bluwp\Foundation\ServiceProvider;

final class HooksServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        (new HookLoader($this->app))->register();
    }
}
