<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

// Protocol v1: this file must stay independent of all framework classes.
if (!class_exists('BluwpBridgeV1', false)) {
    final class BluwpBridgeV1
    {
        private static array $providers = [];
        private static array $consumers = [];
        private static bool $scheduled = false;
        private static bool $resolved = false;

        public static function provide(string $path): void
        {
            self::$providers[$path] = $path;
            self::schedule();
        }

        private static function schedule(): void
        {
            if (!self::$scheduled) {
                self::$scheduled = true;
                add_action('plugins_loaded', [self::class, 'resolve'], -1000);
            }
        }

        public static function consume(string $file, string $name, string $title, string $minimum, string $embedded): void
        {
            if (isset(self::$consumers[$file])) {
                return;
            }
            if (!preg_match('/^\d+\.\d+\.\d+$/D', $minimum)) {
                throw new InvalidArgumentException('Invalid Bluwp requirement.');
            }
            self::$consumers[$file] = compact('file', 'name', 'title', 'minimum', 'embedded');
            $GLOBALS['bluwp_consumer_requirements'][$file] = ['version' => $minimum];
            register_activation_hook($file, static function () use ($file): void {
                self::resolve();
                self::lifecycle($file)->activate();
            });
            register_deactivation_hook($file, static function () use ($file): void {
                self::resolve();
                if (empty($GLOBALS['bluwp_framework_error'])) {
                    self::lifecycle($file)->deactivate();
                }
            });
            self::schedule();
            if (self::$resolved || did_action('plugins_loaded')) {
                self::resolve();
                self::attach($file);
            }
        }

        private static function lifecycle(string $file): object
        {
            if (!empty($GLOBALS['bluwp_framework_error']) || empty($GLOBALS['bluwp_framework_loaded'])) {
                throw new RuntimeException('A complete, compatible Bluwp framework is required.');
            }
            $c = self::$consumers[$file];
            return \Howtowp\Bluwp\Foundation\Bootstrap\PluginBootstrap::register($file, $c['name'], $c['title'], false);
        }

        private static function attach(string $file): void
        {
            $loaded = $GLOBALS['bluwp_framework_loaded'] ?? null;
            if (!$loaded) {
                return;
            }
            $minimum = self::$consumers[$file]['minimum'];
            $parts = explode('.', $minimum);
            $maximum = $parts[0] === '0' ? '0.' . ((int) $parts[1] + 1) . '.0' : ((int) $parts[0] + 1) . '.0.0';
            if (version_compare($loaded['version'], $minimum, '<') || version_compare($loaded['version'], $maximum, '>=')) {
                $GLOBALS['bluwp_framework_error'] = 'The selected Bluwp provider does not satisfy all consumers.';
                return;
            }
            self::lifecycle($file);
        }

        public static function resolve(): void
        {
            if (self::$resolved) {
                return;
            }
            self::$resolved = true;
            $GLOBALS['bluwp_framework_error'] = 'No complete, compatible Bluwp provider is available.';
            // Only providers whose entrypoint WordPress actually included participate.
            // Inactive, missing and recovery-paused plugins never register here.
            $paths = self::$providers;
            if (!$paths) {
                foreach (self::$consumers as $consumer) {
                    $paths[$consumer['embedded']] = $consumer['embedded'];
                }
            }
            $candidates = [];
            foreach ($paths as $path) {
                $path = rtrim($path, '/\\') . '/';
                try {
                    $metadata = is_readable($path . 'metadata.php') ? require $path . 'metadata.php' : null;
                } catch (Throwable) {
                    $metadata = null;
                }
                if (is_array($metadata) && is_string($metadata['version'] ?? null)
                    && preg_match('/^\d+\.\d+\.\d+$/D', $metadata['version'])) {
                    $candidates[] = ['version' => $metadata['version'], 'path' => $path, 'source' => $path];
                }
            }
            usort($candidates, static fn (array $a, array $b): int => version_compare($b['version'], $a['version']) ?: strcmp($a['source'], $b['source']));
            if (!$candidates) {
                return;
            }
            try {
                $path = $candidates[0]['path'];
                foreach (['runtime.php', 'Foundation/Bootstrap/Loader.php', 'Foundation/Bootstrap/PluginBootstrap.php'] as $file) {
                    if (!is_readable($path . $file)) {
                        return;
                    }
                }
                require_once $path . 'runtime.php';
                foreach ($candidates as $candidate) {
                    bluwpFrameworkRegister($candidate['version'], $candidate['path'], $candidate['source']);
                }
                \Howtowp\Bluwp\Foundation\Bootstrap\Loader::load();
                foreach (array_keys(self::$consumers) as $file) {
                    self::attach($file);
                }
            } catch (Throwable $error) {
                $GLOBALS['bluwp_framework_error'] = 'Bluwp initialization failed: ' . $error->getMessage();
            }
        }
    }
}
