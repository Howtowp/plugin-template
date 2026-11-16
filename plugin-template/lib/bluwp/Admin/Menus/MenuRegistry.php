<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Admin\Menus;

use Howtowp\Bluwp\UI\Pages\PageRegistry;
use Howtowp\Bluwp\UI\Support\DefinitionValidator;
use InvalidArgumentException;
use LogicException;

final class MenuRegistry
{
    private ?MainMenu $mainMenu = null;
    /** @var array<string, string> Page ID to WordPress menu slug. */
    private array $slugs = [];

    public function __construct(private PageRegistry $pages)
    {
    }

    public static function fromFile(string $file, PageRegistry $pages): self
    {
        if (!is_file($file) || !is_readable($file)) {
            throw new \RuntimeException(__('Admin menu file is missing or unreadable.', BLUWP));
        }
        $definition = (static fn (string $file) => require $file)($file);
        if (!$definition instanceof \Closure) {
            throw new \UnexpectedValueException(__('Admin menu file must return a closure.', BLUWP));
        }
        $menus = $definition($pages);
        if (!$menus instanceof self) {
            throw new \UnexpectedValueException(__('Admin menu file must return a menu registry.', BLUWP));
        }
        return $menus;
    }

    public function mainMenu(string $title): PendingMainMenu
    {
        return new PendingMainMenu($this, $title);
    }

    public function setMainMenu(MainMenu $menu): self
    {
        if ($this->mainMenu !== null) {
            throw new LogicException(__('Main admin menu is already registered.', BLUWP));
        }
        $slug = $menu->toArray()['menu_slug'];
        if (in_array($slug, $this->slugs, true)) {
            throw new InvalidArgumentException('Main menu slug conflicts with a submenu: ' . $slug);
        }
        $this->mainMenu = $menu;
        return $this;
    }

    /** @return array<string, mixed> */
    public function mainMenuConfig(): array
    {
        if ($this->mainMenu === null) {
            throw new LogicException(__('Main admin menu is not registered.', BLUWP));
        }
        return $this->mainMenu->toArray();
    }

    public function page(string $id, string $slug): self
    {
        DefinitionValidator::identity($id, 'page_id');
        DefinitionValidator::identity($slug, 'menu_slug');
        if ($this->pages->get($id) === null) {
            throw new InvalidArgumentException('Menu refers to an unknown page: ' . $id);
        }
        if (isset($this->slugs[$id]) || in_array($slug, $this->slugs, true)
            || ($this->mainMenu !== null && $this->mainMenu->toArray()['menu_slug'] === $slug)) {
            throw new InvalidArgumentException('Menu page IDs and slugs must be unique.');
        }
        $this->slugs[$id] = $slug;
        return $this;
    }

    /** @return array<string, string> */
    public function pages(): array
    {
        return $this->slugs;
    }
}
