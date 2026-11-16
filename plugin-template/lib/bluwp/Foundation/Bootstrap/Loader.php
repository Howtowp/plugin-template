<?php

declare(strict_types=1);
/**
 * Stable selection protocol. All copies register before plugins_loaded.
 *
 * @package Howtowp\Bluwp
 */

namespace Howtowp\Bluwp\Foundation\Bootstrap;

defined('ABSPATH') || exit;

if (!class_exists(Loader::class, false)) {
    final class Loader
    {
        private const CANDIDATES_KEY = 'bluwp_framework_candidates';
        private const LOADED_KEY = 'bluwp_framework_loaded';
        private const SCHEDULED_KEY = 'bluwp_framework_loader_scheduled';

        public static function register(string $version, string $path, string $source): void
        {
            if (!preg_match('/^\d+\.\d+\.\d+$/D', $version) || '' === $source) {
                throw new \InvalidArgumentException('Bluwp requires a stable semantic version and source.');
            }
            // Activation may include a new plugin after plugins_loaded. Selection
            // is immutable for this request and will run again on the next one.
            $path = trailingslashit($path);
            $GLOBALS[self::CANDIDATES_KEY][$source] = [
                'version' => $version, 'path' => $path, 'source' => $source,
            ];
        }

        public static function schedule(): void
        {
            if (!empty($GLOBALS[self::SCHEDULED_KEY])) {
                return;
            }
            $GLOBALS[self::SCHEDULED_KEY] = true;
            add_action('plugins_loaded', [__CLASS__, 'load'], -1000);
        }

        public static function load(): void
        {
            if (!empty($GLOBALS[self::LOADED_KEY])) {
                return;
            }
            $candidates = array_values($GLOBALS[self::CANDIDATES_KEY] ?? []);
            usort($candidates, static function (array $first, array $second): int {
                $order = version_compare($second['version'], $first['version']);
                return 0 !== $order ? $order : strcmp($first['source'], $second['source']);
            });
            foreach ($candidates as $candidate) {
                if (!self::compatible($candidate['version'], $candidates)) {
                    continue;
                }
                if (!self::ready($candidate)) {
                    continue;
                }
                // Check bootstrap requirements; other classes are loaded only on demand.
                require_once $candidate['path'] . 'Foundation/Bootstrap/Autoloader.php';
                Autoloader::register('Howtowp\\Bluwp', $candidate['path']);
                $GLOBALS[self::LOADED_KEY] = $candidate;
                unset($GLOBALS['bluwp_framework_error']);
                if (did_action('init')) {
                    self::loadTranslations();
                } else {
                    add_action('init', [__CLASS__, 'loadTranslations'], 0);
                }
                return;
            }
            $GLOBALS['bluwp_framework_error'] = 'No complete, compatible Bluwp package is available.';
        }

        public static function loadTranslations(): void
        {
            $framework = $GLOBALS[self::LOADED_KEY] ?? null;
            if (!is_array($framework) || !is_string($framework['path'] ?? null)) {
                return;
            }

            $locale = apply_filters('plugin_locale', determine_locale(), BLUWP);
            $global = trailingslashit(WP_LANG_DIR) . 'plugins/bluwp-' . $locale . '.mo';
            $bundled = $framework['path'] . 'lang/bluwp-' . $locale . '.mo';
            $file = is_readable($global) ? $global : $bundled;
            if (is_readable($file)) {
                load_textdomain(BLUWP, $file);
            }
        }

        private static function compatible(string $version, array $candidates): bool
        {
            foreach (($GLOBALS['bluwp_consumer_requirements'] ?? $candidates) as $candidate) {
                $minimum = $candidate['version'];
                $parts = array_map('intval', explode('.', $minimum));
                // Before 1.0, each minor release can contain breaking changes.
                $maximum = 0 === $parts[0] ? '0.' . ($parts[1] + 1) . '.0' : ($parts[0] + 1) . '.0.0';
                if (version_compare($version, $minimum, '<') || version_compare($version, $maximum, '>=')) {
                    return false;
                }
            }
            return true;
        }

        private static function ready(array $candidate): bool
        {
            $path = $candidate['path'];
            if (!is_readable($path . 'metadata.php')) {
                return false;
            }
            try {
                $metadata = require $path . 'metadata.php';
            } catch (\Throwable) {
                return false;
            }
            if (!is_array($metadata) || ($metadata['version'] ?? null) !== $candidate['version']
                || !is_string($metadata['php'] ?? null)
                || !preg_match('/^\d+\.\d+(?:\.\d+)?$/D', $metadata['php'])
                || version_compare(PHP_VERSION, $metadata['php'], '<')) {
                return false;
            }
            $root = realpath($path);
            if ($root === false) {
                return false;
            }
            foreach ([
                'Foundation/Bootstrap/Loader.php',
                'Foundation/Bootstrap/Autoloader.php',
                'Foundation/Bootstrap/PluginBootstrap.php',
                'Foundation/Bootstrap/DefaultProviders.php',
                'Foundation/Application.php',
                'Foundation/ServiceProvider.php',
                'Container/Container.php',
                'Container/AutowireResolver.php',
            ] as $file) {
                $resolved = realpath($path . $file);
                if ($resolved === false || !is_file($resolved) || !is_readable($resolved)
                    || !str_starts_with($resolved, $root . DIRECTORY_SEPARATOR)) {
                    return false;
                }
            }
            return true;
        }
    }
}
