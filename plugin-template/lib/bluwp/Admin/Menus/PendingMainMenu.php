<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Admin\Menus;

final class PendingMainMenu
{
    private string $pageTitle;
    private string $menuTitle;
    private string $capability = 'manage_options';
    private string $menuSlug = '';
    private mixed $callback = null;
    private string $iconUrl = '';
    private ?int $position = null;
    private bool $registered = false;

    public function __construct(private MenuRegistry $registry, string $title)
    {
        $this->pageTitle = $title;
        $this->menuTitle = $title;
    }

    public function pageTitle(string $title): self
    {
        $this->assertMutable();
        $this->pageTitle = $title;
        return $this;
    }

    public function menuTitle(string $title): self
    {
        $this->assertMutable();
        $this->menuTitle = $title;
        return $this;
    }

    public function capability(string $capability): self
    {
        $this->assertMutable();
        $this->capability = $capability;
        return $this;
    }

    public function slug(string $slug): self
    {
        $this->assertMutable();
        $this->menuSlug = $slug;
        return $this;
    }

    public function callback(callable $callback): self
    {
        $this->assertMutable();
        $this->callback = $callback;
        return $this;
    }

    public function icon(string $icon): self
    {
        $this->assertMutable();
        $this->iconUrl = $icon;
        return $this;
    }

    public function position(?int $position): self
    {
        $this->assertMutable();
        $this->position = $position;
        return $this;
    }

    public function register(): MenuRegistry
    {
        $this->assertMutable();
        $this->registry->setMainMenu(new MainMenu(
            $this->pageTitle,
            $this->menuTitle,
            $this->capability,
            $this->menuSlug,
            $this->callback,
            $this->iconUrl,
            $this->position
        ));
        $this->registered = true;
        return $this->registry;
    }

    private function assertMutable(): void
    {
        if ($this->registered) {
            throw new \LogicException(__('Main admin menu is already registered.', BLUWP));
        }
    }
}
