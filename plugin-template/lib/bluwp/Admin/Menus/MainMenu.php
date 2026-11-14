<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Admin\Menus;

use Forouzandea\Bluwp\Admin\Support\DefinitionValidator;

final class MainMenu
{
    public function __construct(
        private readonly string $pageTitle,
        private readonly string $menuTitle,
        private readonly string $capability,
        private readonly string $menuSlug,
        private readonly mixed $callback,
        private readonly string $iconUrl,
        private readonly ?int $position
    ) {
        DefinitionValidator::menu($this->toArray());
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'page_title' => $this->pageTitle,
            'menu_title' => $this->menuTitle,
            'capability' => $this->capability,
            'menu_slug' => $this->menuSlug,
            'callback' => $this->callback,
            'icon_url' => $this->iconUrl,
            'position' => $this->position,
        ];
    }
}
