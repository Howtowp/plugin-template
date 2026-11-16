<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database;

use Howtowp\Bluwp\Database\Connection\ConnectionInterface;
use Howtowp\Bluwp\Database\Connection\WPDBConnection;

final class DatabaseRegistry
{
    /** @var array<string, DatabaseManager|\Closure(): DatabaseManager> */
    private static array $managers = [];
    private static array $namespaces = [];
    private static ?\WeakMap $wordpressAdapters = null;

    public static function register(string $plugin, string $namespace, DatabaseManager|\Closure $manager): void
    {
        $namespace = strtolower(trim($namespace, '\\'));
        if ($plugin === '' || $namespace === '' || isset(self::$managers[$plugin])) {
            throw new \InvalidArgumentException('A database manager needs a unique plugin ID and namespace.');
        }
        foreach (self::$namespaces as $registered => $owner) {
            if ($namespace === $registered || str_starts_with($namespace . '\\', $registered . '\\')
                || str_starts_with($registered . '\\', $namespace . '\\')) {
                throw new \LogicException('Database model namespaces cannot overlap.');
            }
        }
        self::$managers[$plugin] = $manager;
        self::$namespaces[$namespace] = $plugin;
    }

    public static function forPlugin(string $plugin): DatabaseManager
    {
        $manager = self::$managers[$plugin] ?? throw new \LogicException('Unknown database plugin scope: ' . $plugin);
        if ($manager instanceof \Closure) {
            self::$managers[$plugin] = $manager = $manager();
        }
        return $manager;
    }

    public static function forModel(string $class): DatabaseManager
    {
        $className = strtolower(ltrim($class, '\\'));
        foreach (self::$namespaces as $namespace => $plugin) {
            if (str_starts_with($className, $namespace . '\\')) {
                return self::forPlugin($plugin);
            }
        }
        throw new \LogicException('Register the model namespace or inject a connection: ' . $class);
    }

    public static function wordpressAdapter(): ConnectionInterface
    {
        global $wpdb;
        if (!$wpdb instanceof \wpdb) {
            throw new \LogicException('WordPress database is unavailable.');
        }
        // The underlying wpdb session is shared: savepoints must share one coordinator.
        self::$wordpressAdapters ??= new \WeakMap();
        return self::$wordpressAdapters[$wpdb] ??= new WPDBConnection($wpdb);
    }
}
