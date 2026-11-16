<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\UI\Tabs;

use Howtowp\Bluwp\UI\Contracts\Schema;

final class Tab implements \JsonSerializable
{
    public function __construct(
        private string $id,
        private string $title,
        private Schema $content,
        private string $icon = 'settings',
        private string $description = ''
    ) {
        if (!preg_match('/^[a-z][a-z0-9-]*$/D', $id) || trim($title) === '' || trim($icon) === '') {
            throw new \InvalidArgumentException('A tab requires a valid ID, title and icon.');
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'icon' => $this->icon,
            'description' => $this->description,
            'content' => $this->content->toArray(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
