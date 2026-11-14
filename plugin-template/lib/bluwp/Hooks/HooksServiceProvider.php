<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Hooks;

use Forouzandea\Bluwp\Foundation\ServiceProvider;

final class HooksServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        (new HookLoader($this->app))->register();
    }
}
