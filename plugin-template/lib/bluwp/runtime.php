<?php

declare(strict_types=1);
/**
 * Bluwp shared framework loader.
 *
 * @package Forouzandea\Bluwp
 */

use Forouzandea\Bluwp\Foundation\Bootstrap\Loader;
use Forouzandea\Bluwp\Foundation\Bootstrap\PluginBootstrap;

defined('ABSPATH') || exit;

defined('BLUWP') || define('BLUWP', 'bluwp');

$bluwp_framework_loader_file = __DIR__ . DIRECTORY_SEPARATOR . 'Foundation/Bootstrap/Loader.php';
$bluwp_framework_loader_class = '\Forouzandea\Bluwp\Foundation\Bootstrap\Loader';

if (!class_exists($bluwp_framework_loader_class, false) && file_exists($bluwp_framework_loader_file)) {
    require_once $bluwp_framework_loader_file;
}

if (!function_exists('bluwpFrameworkRegister')) {
    function bluwpFrameworkRegister(string $version, string $path, string $source): void
    {
        if (class_exists('\Forouzandea\Bluwp\Foundation\Bootstrap\Loader', false)) {
            Loader::register($version, $path, $source);
        }
    }
}

if (class_exists($bluwp_framework_loader_class, false)) {
    $bluwp_framework_loader_class::schedule();
}

$bluwp_plugin_bootstrap_file = __DIR__ . '/Foundation/Bootstrap/PluginBootstrap.php';
if (!class_exists('\\Forouzandea\\Bluwp\\Foundation\\Bootstrap\\PluginBootstrap', false)
    && is_readable($bluwp_plugin_bootstrap_file)) {
    require_once $bluwp_plugin_bootstrap_file;
}

if (!function_exists('bluwpPluginRegister')) {
    function bluwpPluginRegister(string $file, string $name, string $title, string $path): void
    {
        $metadataFile = rtrim($path, '/\\') . '/metadata.php';
        $metadata = null;
        if (is_readable($metadataFile)) {
            try {
                $metadata = require $metadataFile;
            } catch (\Throwable) {
                // Keep lifecycle guards available when package metadata is broken.
            }
        }
        if (is_array($metadata) && is_string($metadata['version'] ?? null)
            && preg_match('/^\d+\.\d+\.\d+$/D', $metadata['version'])) {
            bluwpFrameworkRegister($metadata['version'], $path, plugin_basename($file));
        }
        if (!class_exists('\\Forouzandea\\Bluwp\\Foundation\\Bootstrap\\PluginBootstrap', false)) {
            throw new \RuntimeException('The plugin lifecycle bridge is missing.');
        }
        PluginBootstrap::register($file, $name, $title);
    }
}
