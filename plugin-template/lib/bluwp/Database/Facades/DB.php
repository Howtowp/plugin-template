<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Facades;

use Forouzandea\Bluwp\Database\DatabaseManager;
use Forouzandea\Bluwp\Database\DatabaseRegistry;

class DB
{
    protected static string $plugin = '';

    public static function forPlugin(string $plugin): DatabaseManager
    {
        return DatabaseRegistry::forPlugin($plugin);
    }

    public static function __callStatic(string $method, array $arguments): mixed
    {
        if (static::$plugin === '') {
            throw new \LogicException('Use DB::forPlugin(id) or a plugin-scoped DB facade.');
        }
        $manager = self::forPlugin(static::$plugin);
        return $manager->$method(...$arguments);
    }
}
