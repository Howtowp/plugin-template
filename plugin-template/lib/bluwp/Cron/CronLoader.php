<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Cron;

use Forouzandea\Bluwp\Foundation\Application;

final class CronLoader
{
    public function load(string $directory, CronScheduler $scheduler, Application $app): int
    {
        $root = realpath($directory);
        if ($root === false) {
            return 0;
        }
        if (!is_dir($root) || !is_readable($root)) {
            throw new \RuntimeException('Cron definition directory is not readable.');
        }
        $files = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $path = realpath($file->getPathname());
                if ($path === false || !str_starts_with(strtolower($path), strtolower($root . DIRECTORY_SEPARATOR))) {
                    throw new \RuntimeException('Cron definition must stay inside its directory.');
                }
                $files[] = $path;
            }
        }
        sort($files, SORT_STRING);
        foreach ($files as $file) {
            $definition = require $file;
            if (!$definition instanceof CronDefinition) {
                throw new \RuntimeException('Cron file must return a CronDefinition: ' . $file);
            }
            $definition->register($scheduler, $app);
        }
        return count($files);
    }
}
