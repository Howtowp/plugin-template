<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Admin;

use Forouzandea\Bluwp\UI\Controllers\PagesController;
use Forouzandea\Bluwp\UI\AppConfig;
use Forouzandea\Bluwp\UI\AppAssets;
use Forouzandea\Bluwp\UI\Authorization\ViewPages;
use Forouzandea\Bluwp\Admin\Menus\MenuRegistry;
use Forouzandea\Bluwp\Admin\Menus\AppMenu;
use Forouzandea\Bluwp\Admin\Menus\Menu;
use Forouzandea\Bluwp\UI\Views\AppView;
use Forouzandea\Bluwp\UI\Views\AppViewConfiguration;
use Forouzandea\Bluwp\UI\Pages\PageRegistry;
use Forouzandea\Bluwp\Foundation\Application;
use Forouzandea\Bluwp\Foundation\ServiceProvider;
use Forouzandea\Bluwp\UI\UIServiceProvider;
use Forouzandea\Bluwp\Admin\Contracts\NoticePolicy;
use Forouzandea\Bluwp\Admin\Notices\WordPressNoticePolicy;
use Forouzandea\Bluwp\Admin\Notices\MigrationFailureNotice;

final class AdminServiceProvider extends ServiceProvider
{
    public const PAGES = 'admin.pages';
    public const CONFIG = 'admin.config';
    public const ASSETS = 'admin.assets';
    public const PAGES_CONTROLLER = 'admin.pages.controller';
    public const PAGES_PERMISSION = 'admin.pages.permission';
    public const VIEW = 'admin.view';
    public const VIEW_CONFIGURATION = 'admin.view.configuration';
    public const STARTUP_NOTICE = 'admin.startup.notice';

    public function register(): void
    {
        $this->app->deferProvider(UIServiceProvider::class);
        $this->registerApp();
        $this->registerPages();
        $this->registerMenus();
    }

    private function registerApp(): void
    {
        $this->singleton(self::STARTUP_NOTICE, static fn (Application $app) => new MigrationFailureNotice($app));
        $this->singleton(self::CONFIG, static function (Application $app): AppConfig {
            $admin = $app->config('admin', []);
            $name = $app->config('name', '');
            $title = $admin['title'] ?? $name;
            return new AppConfig(
                $name,
                $title instanceof \Closure ? $title() : $title,
                $admin['icon'] ?? '',
                $admin['rest_namespace'] ?? $name . '/v1',
                $admin['pages_endpoint'] ?? 'admin/pages',
                $admin['messages'] ?? [],
                $admin['navigation'] ?? false
            );
        });
        $this->singleton(self::ASSETS, static function (Application $app): AppAssets {
            $assets = $app->config('admin', [])['assets'] ?? [];
            $handle = $assets['handle'] ?? $app->config('name') . '-admin';
            return new AppAssets(
                $handle,
                $app->config('path'),
                $app->config('url'),
                $app->config('version'),
                ($assets['files'] ?? []) ?: [
                    'css/style.css' => 'style',
                    'css/admin.css' => ['type' => 'style', 'handle' => $handle . '-host', 'deps' => [$handle]],
                    'js/script.js' => 'script',
                ],
                $assets['suppress_footer'] ?? true
            );
        });
        $this->singleton(self::VIEW, static function (Application $app): AppView {
            $admin = $app->config('admin', []);
            $view = $admin['view'] ?? [];
            return new AppView(
                $view['id'] ?? $app->config('name') . '-admin-app',
                $app->get(self::PAGES),
                $app->get(self::VIEW_CONFIGURATION)
            );
        });
        $this->singleton(self::VIEW_CONFIGURATION, static fn (Application $app) => new AppViewConfiguration(
            $app->get(self::CONFIG),
            $app->get(self::STARTUP_NOTICE)
        ));
    }

    private function registerPages(): void
    {
        $this->singleton(self::PAGES, static function (Application $app): PageRegistry {
            $pages = new PageRegistry();
            $path = rtrim((string) $app->config('path', ''), '/\\');
            $definition = $path . '/pages/admin.php';
            return $path !== '' && is_file($definition) ? $pages->load($definition) : $pages;
        });
        $this->singleton(self::PAGES_CONTROLLER, static fn (Application $app) => new PagesController(
            $app->get(self::PAGES),
            $app->get(self::CONFIG)
        ));
        $this->singleton(self::PAGES_PERMISSION, static fn (Application $app) => new ViewPages($app->get(self::PAGES)));
    }

    private function registerMenus(): void
    {
        $this->singleton(NoticePolicy::class, static fn () => new WordPressNoticePolicy());
        $this->singleton(MenuRegistry::class, static function (Application $app): MenuRegistry {
            $pages = $app->get(self::PAGES);
            $path = rtrim((string) $app->config('path', ''), '/\\');
            $file = $path . '/pages/admin-menu.php';
            return $path !== '' && is_file($file) ? MenuRegistry::fromFile($file, $pages) : new MenuRegistry($pages);
        });
        $this->singleton(AppMenu::class, static fn (Application $app) => new AppMenu(
            $app->get(Menu::class),
            $app->get(self::VIEW),
            $app->get(self::PAGES),
            $app->get(MenuRegistry::class),
            noticePolicy: $app->get(NoticePolicy::class),
            rootPage: $app->config('admin', [])['root_page'] ?? null
        ));
    }

    public function boot(): void
    {
        add_action('admin_menu', fn () => $this->app->get(AppMenu::class)->register());
        add_action('admin_enqueue_scripts', function (string $hook): void {
            if ($this->app->get(AppMenu::class)->ownsHook($hook)) {
                $this->app->get(self::ASSETS)->enqueue();
                add_filter('admin_body_class', static function (string $classes): string {
                    return in_array('bluwp-admin-screen', preg_split('/\s+/', trim($classes)) ?: [], true)
                        ? $classes : $classes . ' bluwp-admin-screen ';
                });
            }
        });
    }
}
