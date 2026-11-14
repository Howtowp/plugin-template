<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\UI\Tabs;

use Forouzandea\Bluwp\UI\Contracts\Schema;

abstract class TabbedSchema implements Schema
{
    abstract public function title(): string;

    /** @return list<Tab> */
    abstract public function tabs(): array;

    public function defaultTab(): ?string
    {
        return null;
    }

    public function toArray(): array
    {
        $tabs = [];
        foreach ($this->tabs() as $tab) {
            if (!$tab instanceof Tab || isset($tabs[$tab->id()])) {
                throw new \LogicException('Tabbed schemas require Tab objects with unique IDs.');
            }
            $tabs[$tab->id()] = $tab->toArray();
        }
        if ($tabs === []) {
            throw new \LogicException('A tabbed schema requires at least one tab.');
        }
        $default = $this->defaultTab() ?? array_key_first($tabs);
        if (!isset($tabs[$default])) {
            throw new \LogicException('The default tab must exist.');
        }
        return [
            'title' => $this->title(),
            'type' => 'tabs',
            'defaultTab' => $default,
            'tabs' => array_values($tabs),
        ];
    }
}
