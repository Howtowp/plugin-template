<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database\Pagination;

use Howtowp\Bluwp\Support\Collection;

class LengthAwarePaginator extends AbstractPaginator
{
    protected int $total;
    protected int $lastPage;
    public function __construct(iterable $items, int $total, int $perPage, ?int $currentPage = null, array $options = [])
    {
        $this->configure($items, $perPage, $currentPage, $options);
        $this->total = max(0, $total);
        $this->lastPage = max(1, (int) ceil($this->total / $this->perPage));
    }
    public function total(): int
    {
        return $this->total;
    }
    public function lastPage(): int
    {
        return $this->lastPage;
    }
    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage;
    }
    public function linkCollection(): Collection
    {
        $links = [['url' => $this->previousPageUrl(), 'label' => 'Previous',
            'page' => $this->currentPage > 1 ? $this->currentPage - 1 : null, 'active' => false]];
        // Keep the serialized link window bounded for very large result sets.
        if ($this->lastPage < $this->onEachSide * 2 + 8) {
            $pages = range(1, $this->lastPage);
        } elseif ($this->currentPage <= $this->onEachSide + 3) {
            $pages = array_merge(range(1, $this->onEachSide * 2 + 3), [$this->lastPage - 1, $this->lastPage]);
        } elseif ($this->currentPage > $this->lastPage - ($this->onEachSide + 3)) {
            $pages = array_merge([1, 2], range(max(3, $this->lastPage - $this->onEachSide * 2 - 2), $this->lastPage));
        } else {
            $pages = array_merge([1, 2], range($this->currentPage - $this->onEachSide, $this->currentPage + $this->onEachSide), [$this->lastPage - 1, $this->lastPage]);
        }
        $previous = 0;
        foreach (array_unique($pages) as $page) {
            if ($page > $previous + 1) {
                $links[] = ['url' => null, 'label' => '...', 'active' => false];
            }
            $links[] = ['url' => $this->url($page), 'label' => (string) $page, 'page' => $page, 'active' => $page === $this->currentPage];
            $previous = $page;
        }
        $links[] = ['url' => $this->nextPageUrl(), 'label' => 'Next', 'page' => $this->hasMorePages() ? $this->currentPage + 1 : null, 'active' => false];
        return new Collection($links);
    }
    public function toArray(): array
    {
        return ['current_page' => $this->currentPage(), 'data' => $this->items->toArray(),
            'first_page_url' => $this->url(1), 'from' => $this->firstItem(), 'last_page' => $this->lastPage(),
            'last_page_url' => $this->url($this->lastPage()), 'links' => $this->linkCollection()->toArray(),
            'next_page_url' => $this->nextPageUrl(), 'path' => $this->path(), 'per_page' => $this->perPage(),
            'prev_page_url' => $this->previousPageUrl(), 'to' => $this->lastItem(), 'total' => $this->total()];
    }
}
