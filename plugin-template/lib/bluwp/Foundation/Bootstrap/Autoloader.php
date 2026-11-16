<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Foundation\Bootstrap;

final class Autoloader
{
    private static array $roots = [];

    public static function register(string $prefix, string $directory): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        $root = realpath($directory);
        if ($root === false || !is_dir($root)) {
            throw new \InvalidArgumentException('Autoload directory does not exist.');
        }
        if (isset(self::$roots[$prefix])) {
            if (self::$roots[$prefix] !== $root) {
                throw new \LogicException('Namespace already belongs to another directory: ' . $prefix);
            }
            return;
        }
        self::$roots[$prefix] = $root;
        spl_autoload_register(static function (string $class) use ($prefix, $root): void {
            if (!str_starts_with($class, $prefix)) {
                return;
            }
            $relative = substr($class, strlen($prefix));
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*$/D', $relative)) {
                return;
            }
            $file = $root . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
            if (is_file($file) && is_readable($file)) {
                require_once $file;
            }
        });
    }
}
