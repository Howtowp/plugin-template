<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Foundation\Bootstrap;

use Forouzandea\Bluwp\Foundation\Application;
use Forouzandea\Bluwp\Cron\CronScheduler;
use Forouzandea\Bluwp\Cron\CronServiceProvider;
use Forouzandea\Bluwp\Database\Migrations\AutomaticMigrator;

// Like Loader, this small lifecycle bridge is available before shared-framework selection.
if (!class_exists(PluginBootstrap::class, false)) {
    final class PluginBootstrap
    {
        /** @var array<string, self> */
        private static array $plugins = [];
        private ?Application $application = null;
        private bool $initialized = false;
        private bool $noticeRegistered = false;

        private function __construct(private string $file, private string $name, private string $title)
        {
        }

        public static function register(string $file, string $name, string $title, bool $registerHooks = true): self
        {
            if (!is_file($file) || !preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/D', $name) || $title === '') {
                throw new \InvalidArgumentException('Plugin bootstrap requires a plugin file, name and title.');
            }
            if (isset(self::$plugins[$file])) {
                $plugin = self::$plugins[$file];
                if ($plugin->name !== $name || $plugin->title !== $title) {
                    throw new \LogicException('Conflicting plugin bootstrap registration.');
                }
                return $plugin;
            }
            $plugin = new self($file, $name, $title);
            self::$plugins[$file] = $plugin;
            if ($registerHooks) {
                register_activation_hook($file, [$plugin, 'activate']);
                register_deactivation_hook($file, [$plugin, 'deactivate']);
            }
            if (did_action('init')) {
                if (class_exists(Loader::class, false)) {
                    Loader::load();
                }
                $plugin->boot();
            } else {
                add_action('init', [$plugin, 'boot'], 1);
            }
            return $plugin;
        }

        public static function forPlugin(string $file): self
        {
            return self::$plugins[$file] ?? throw new \LogicException('Plugin bootstrap is not registered.');
        }

        public function app(): Application
        {
            if (!$this->available()) {
                throw new \RuntimeException('A compatible Bluwp framework is required.');
            }
            if ($this->application === null) {
                $app = Application::fromPlugin($this->file);
                if ($app->config('name') !== $this->name) {
                    throw new \LogicException('Plugin name must match its application configuration.');
                }
                $this->application = $app;
            }
            return $this->application;
        }

        public function boot(): void
        {
            if (!$this->available()) {
                if (!$this->noticeRegistered) {
                    add_action('admin_notices', [$this, 'renderNotice']);
                    $this->noticeRegistered = true;
                }
                return;
            }
            if (!$this->initialized) {
                $this->app()->initialize();
                $this->initialized = true;
            }
        }

        public function activate(): void
        {
            if (!class_exists(Loader::class, false)) {
                throw new \RuntimeException('A compatible Bluwp loader is required for activation.');
            }
            Loader::load();
            $app = $this->app();
            $this->boot();
            if ($app->has(AutomaticMigrator::class)) {
                $app->get(AutomaticMigrator::class)->run(true);
            }
            update_option($this->name . '_version', $app->config('version'), false);
        }

        public function deactivate(): void
        {
            if (!class_exists(Loader::class, false)) {
                return;
            }
            Loader::load();
            if (!$this->available()) {
                return;
            }
            $app = $this->app();
            if (!$this->initialized && ($app->config('features', [])['cron'] ?? true)) {
                $app->registerProvider(CronServiceProvider::class);
                $app->boot();
            }
            if ($app->has(CronScheduler::class) && !$app->get(CronScheduler::class)->clearRegistered()) {
                throw new \RuntimeException('Could not clear the plugin scheduled events.');
            }
        }

        public function renderNotice(): void
        {
            if (current_user_can('activate_plugins')) {
                echo '<div class="notice notice-error"><p><strong>' . esc_html($this->title) . ':</strong> '
                    . esc_html__('The required framework is missing or incompatible with this version. Update or reinstall the plugin.', $this->name)
                    . '</p></div>';
            }
        }

        private function available(): bool
        {
            return empty($GLOBALS['bluwp_framework_error'])
                && !empty($GLOBALS['bluwp_framework_loaded'])
                && class_exists(Application::class);
        }
    }
}
