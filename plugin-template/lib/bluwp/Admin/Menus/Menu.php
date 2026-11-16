<?php

declare(strict_types=1);
/**
 * Reusable WordPress admin menu registrar.
 *
 * @package Howtowp\Bluwp\Admin
 */

namespace Howtowp\Bluwp\Admin\Menus;

use Howtowp\Bluwp\Admin\Support\DefinitionValidator;

defined('ABSPATH') || exit;

class Menu
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $pages = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $submenus = [];

    /**
     * @var array<string, string>
     */
    private array $hookSuffixes = [];
    private array $pageSlugs = [];
    private array $submenuParents = [];

    /**
     * @param array<int, array<string, mixed>> $pages Main menu pages.
     */
    public function __construct(array $pages = [])
    {
        foreach ($pages as $page) {
            $this->addPage($page);
        }
    }

    /**
     * Add a main menu page.
     *
     * @param array<string, mixed> $page Menu registration arguments.
     */
    public function addPage(array $page): self
    {
        $page = wp_parse_args(
            $page,
            [
                'page_title' => '',
                'menu_title' => '',
                'capability' => 'manage_options',
                'menu_slug'  => '',
                'callback'   => null,
                'icon_url'   => '',
                'position'   => null,
            ]
        );
        DefinitionValidator::menu($page);
        $slug = $page['menu_slug'];
        if (isset($this->pageSlugs[$slug]) || (isset($this->submenuParents[$slug]) && $this->submenuParents[$slug] !== $slug)) {
            throw new \InvalidArgumentException('Duplicate admin menu slug: ' . $slug);
        }
        $this->pageSlugs[$slug] = true;
        $this->pages[] = $page;

        return $this;
    }

    /**
     * Add a submenu page.
     *
     * @param array<string, mixed> $submenu Submenu registration arguments.
     */
    public function addSubmenuPage(array $submenu): self
    {
        $submenu = wp_parse_args(
            $submenu,
            [
                'parent_slug' => '',
                'page_title'  => '',
                'menu_title'  => '',
                'capability'  => 'manage_options',
                'menu_slug'   => '',
                'callback'    => null,
                'position'    => null,
            ]
        );
        DefinitionValidator::menu($submenu, true);
        $slug = $submenu['menu_slug'];
        if (isset($this->submenuParents[$slug]) || (isset($this->pageSlugs[$slug]) && $submenu['parent_slug'] !== $slug)) {
            throw new \InvalidArgumentException('Duplicate admin submenu slug: ' . $slug);
        }
        $this->submenuParents[$slug] = $submenu['parent_slug'];
        $this->submenus[] = $submenu;

        return $this;
    }

    /**
     * Register all configured menus with WordPress.
     */
    public function register(): void
    {
        foreach ($this->pages as $page) {
            $this->registerPage($page);
        }

        foreach ($this->submenus as $submenu) {
            $this->registerSubmenuPage($submenu);
        }
    }

    /**
     * Get a registered hook suffix by menu slug.
     */
    public function hookSuffix(string $menu_slug): string
    {
        return $this->hookSuffixes[ $menu_slug ] ?? '';
    }

    /**
     * @param array<string, mixed> $page Menu registration arguments.
     */
    private function registerPage(array $page): void
    {
        $hookSuffix = (string) add_menu_page(
            (string) $page['page_title'],
            (string) $page['menu_title'],
            (string) $page['capability'],
            (string) $page['menu_slug'],
            $this->callback($page['callback']),
            (string) $page['icon_url'],
            $page['position']
        );

        $this->hookSuffixes[ (string) $page['menu_slug'] ] = $hookSuffix;
    }

    /**
     * @param array<string, mixed> $submenu Submenu registration arguments.
     */
    private function registerSubmenuPage(array $submenu): void
    {
        $hookSuffix = (string) add_submenu_page(
            (string) $submenu['parent_slug'],
            (string) $submenu['page_title'],
            (string) $submenu['menu_title'],
            (string) $submenu['capability'],
            (string) $submenu['menu_slug'],
            $this->callback($submenu['callback']),
            $submenu['position']
        );

        $this->hookSuffixes[ (string) $submenu['menu_slug'] ] = $hookSuffix;
    }

    /**
     * @param mixed $callback Possible WordPress callback.
     */
    private function callback($callback): callable
    {
        DefinitionValidator::callback($callback);
        if (is_callable($callback)) {
            return $callback;
        }

        return static function (): void {
            echo '';
        };
    }
}
