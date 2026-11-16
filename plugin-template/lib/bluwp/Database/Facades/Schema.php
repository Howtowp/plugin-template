<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database\Facades;

use Howtowp\Bluwp\Database\DatabaseRegistry;
use Howtowp\Bluwp\Database\Schema\Builder;

class Schema
{
    protected static string $plugin = '';

    public static function forPlugin(string $plugin, ?string $connection = null): Builder
    {
        return DatabaseRegistry::forPlugin($plugin)->connection($connection)->getSchemaBuilder();
    }

    public static function connection(?string $name = null): Builder
    {
        if (static::$plugin === '') {
            throw new \LogicException('Use Schema::forPlugin(id) or a plugin-scoped Schema facade.');
        }
        return self::forPlugin(static::$plugin, $name);
    }

    public static function __callStatic(string $method, array $arguments): mixed
    {
        $schema = static::connection();
        if (!is_callable([$schema, $method])) {
            throw new \BadMethodCallException('Unknown schema operation: ' . $method);
        }
        return $schema->$method(...$arguments);
    }
}
