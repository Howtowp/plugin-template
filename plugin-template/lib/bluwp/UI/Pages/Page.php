<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\UI\Pages;

use Forouzandea\Bluwp\UI\Contracts\Page as PageContract;
use Forouzandea\Bluwp\UI\Support\DefinitionValidator;

final class Page implements PageContract
{
    public function __construct(
        private readonly string $id,
        private readonly string $title,
        private readonly string $type,
        private readonly string $schemaEndpoint,
        private readonly string $capability
    ) {
        DefinitionValidator::identity($id, 'page_id');
        DefinitionValidator::text($title, 'title');
        DefinitionValidator::text($type, 'type');
        DefinitionValidator::text($schemaEndpoint, 'schema_endpoint');
        DefinitionValidator::capability($capability);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function capability(): string
    {
        return $this->capability;
    }

    public function canView(): bool
    {
        return current_user_can($this->capability);
    }

    public function toArray(): array
    {
        return ['id' => $this->id, 'title' => $this->title, 'type' => $this->type, 'schemaEndpoint' => $this->schemaEndpoint];
    }
}
