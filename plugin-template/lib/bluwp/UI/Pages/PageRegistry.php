<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\UI\Pages;

use Howtowp\Bluwp\UI\Contracts\Page as PageContract;
use Howtowp\Bluwp\UI\Support\DefinitionValidator;
use InvalidArgumentException;

final class PageRegistry
{
    private array $pages = [];
    private array $loadedFiles = [];

    public function page(string $id): PendingPage
    {
        return new PendingPage($this, $id);
    }

    public function add(PageContract $page): self
    {
        DefinitionValidator::identity($page->id(), 'page_id');
        DefinitionValidator::text($page->title(), 'title');
        DefinitionValidator::capability($page->capability());
        if (isset($this->pages[$page->id()])) {
            throw new InvalidArgumentException('Page IDs must be unique within a panel.');
        }
        $this->pages[$page->id()] = $page;
        return $this;
    }

    public function get(string $id): ?PageContract
    {
        return $this->pages[$id] ?? null;
    }

    public function load(string $path): self
    {
        $resolved = realpath($path);
        if ($resolved !== false && isset($this->loadedFiles[$resolved])) {
            return $this;
        }
        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException(__('Page file is missing or unreadable.', BLUWP));
        }
        $definition = (static fn (string $file) => require $file)($path);
        if (!$definition instanceof \Closure) {
            throw new \UnexpectedValueException(__('Page file must return a closure.', BLUWP));
        }

        $pages = $this->pages;
        $loadedFiles = $this->loadedFiles;
        try {
            $definition($this);
        } catch (\Throwable $error) {
            $this->pages = $pages;
            $this->loadedFiles = $loadedFiles;
            throw $error;
        }
        $this->loadedFiles[$resolved] = true;
        return $this;
    }

    /** @return list<PageContract> */
    public function all(): array
    {
        return array_values($this->pages);
    }

    public function pages(): array
    {
        return array_values(array_map(
            static fn (PageContract $page): array => $page->toArray(),
            $this->visible()
        ));
    }

    public function canView(string $id): bool
    {
        $page = $this->get($id);
        return $page !== null && current_user_can($page->capability()) && $page->canView();
    }

    /** @return list<PageContract> */
    public function visible(): array
    {
        return array_values(array_filter($this->all(), fn (PageContract $page): bool => $this->canView($page->id())));
    }
}
