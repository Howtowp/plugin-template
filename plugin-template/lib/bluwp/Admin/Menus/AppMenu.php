<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Admin\Menus;

use Howtowp\Bluwp\UI\Views\AppView;
use Howtowp\Bluwp\UI\Pages\PageRegistry;
use Howtowp\Bluwp\Admin\Contracts\NoticePolicy;
use Howtowp\Bluwp\Admin\Notices\WordPressNoticePolicy;

final class AppMenu
{
    private array $hooks = [];
    private string $rootHook = '';

    public function __construct(
        private Menu $menu,
        private AppView $view,
        private PageRegistry $pages,
        private MenuRegistry $definitions,
        private bool $suppressNotices = true,
        private bool $assetsOnRoot = false,
        private NoticePolicy $noticePolicy = new WordPressNoticePolicy(),
        private ?string $rootPage = null
    ) {
    }

    public function register(): void
    {
        if ($this->rootHook !== '') {
            return;
        }
        $root = $this->definitions->mainMenuConfig();
        if ($this->rootPage !== null) {
            if ($this->pages->get($this->rootPage) === null) {
                throw new \InvalidArgumentException('Main admin menu refers to an unknown page: ' . $this->rootPage);
            }
            $root['callback'] = fn () => $this->view->render($this->rootPage);
            $this->assetsOnRoot = true;
        }
        $slugs = $this->definitions->pages();
        $visible = array_values(array_filter($this->pages->visible(), static fn ($page): bool => isset($slugs[$page->id()])));
        if (!current_user_can((string) $root['capability']) && $visible !== []) {
            // The parent becomes a link to an allowed child; its protected callback is never exposed.
            $first = $visible[0];
            $root['capability'] = $first->capability();
            $root['callback'] = fn () => $this->view->render($first->id());
            $this->assetsOnRoot = true;
        }
        $this->menu->addPage($root);
        foreach ($visible as $page) {
            $this->menu->addSubmenuPage([
                'parent_slug' => $root['menu_slug'],
                'page_title' => $page->title(),
                'menu_title' => $page->title(),
                'capability' => $page->capability(),
                'menu_slug' => $slugs[$page->id()],
                'callback' => fn () => $this->view->render($page->id()),
            ]);
        }
        $this->menu->register();
        $this->rootHook = $this->menu->hookSuffix((string) $root['menu_slug']);
        foreach ($visible as $page) {
            $hook = $this->menu->hookSuffix($slugs[$page->id()]);
            if ($hook !== '') {
                $this->hooks[] = $hook;
            }
        }
        if ($this->suppressNotices) {
            $this->noticePolicy->register(array_values(array_filter([$this->rootHook, ...$this->hooks])));
        }
    }

    public function suppressWordPressNotices(): void
    {
        $this->noticePolicy->suppress();
    }

    public function ownsHook(string $hook): bool
    {
        return $hook !== '' && (in_array($hook, $this->hooks, true) || ($this->assetsOnRoot && $hook === $this->rootHook));
    }
}
