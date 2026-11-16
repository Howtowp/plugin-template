<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\UI\Pages;

final class PendingPage
{
    private string $title = '';
    private string $type = '';
    private string $schemaEndpoint = '';
    private string $capability = '';
    private bool $registered = false;

    public function __construct(private readonly PageRegistry $registry, private readonly string $id)
    {
    }

    public function title(string $title): self
    {
        $this->assertMutable();
        $this->title = $title;
        return $this;
    }

    public function table(string $schemaEndpoint): self
    {
        return $this->type('table', $schemaEndpoint);
    }

    public function tabs(string $schemaEndpoint): self
    {
        return $this->type('tabs', $schemaEndpoint);
    }

    public function form(string $schemaEndpoint): self
    {
        return $this->type('form', $schemaEndpoint);
    }

    public function type(string $type, string $schemaEndpoint): self
    {
        $this->assertMutable();
        $this->type = $type;
        $this->schemaEndpoint = $schemaEndpoint;
        return $this;
    }

    public function capability(string $capability): self
    {
        $this->assertMutable();
        $this->capability = $capability;
        return $this;
    }

    public function register(): PageRegistry
    {
        $this->assertMutable();
        $this->registry->add(new Page(
            $this->id,
            $this->title,
            $this->type,
            $this->schemaEndpoint,
            $this->capability
        ));
        $this->registered = true;
        return $this->registry;
    }

    private function assertMutable(): void
    {
        if ($this->registered) {
            throw new \LogicException(__('Page is already registered.', BLUWP));
        }
    }
}
