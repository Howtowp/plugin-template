<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Hooks;

use Forouzandea\Bluwp\Foundation\Application;

final class HookLoader
{
    private bool $registered = false;

    public function __construct(private Application $app)
    {
    }

    public function register(): void
    {
        if ($this->registered) {
            return;
        }
        $root = rtrim((string) $this->app->config('path', ''), '/\\');
        if ($root === '') {
            return;
        }
        $files = glob($root . '/hooks/*.php');
        if ($files === false) {
            throw new \RuntimeException('Unable to discover hook definitions.');
        }
        sort($files, SORT_STRING);
        $hooks = new HookRegistry($this->app);
        foreach ($files as $file) {
            $definition = require $file;
            if (!$definition instanceof \Closure) {
                throw new \LogicException('Hook definition must return a closure: ' . $file);
            }
            $definition($hooks);
        }
        $hooks->register();
        $this->registered = true;
    }
}
