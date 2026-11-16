<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database\Pagination;

class Paginator extends AbstractPaginator
{
    protected bool $hasMore;
    public function __construct(iterable $items, int $perPage, ?int $currentPage = null, array $options = [])
    {
        $this->configure($items, $perPage, $currentPage, $options);
        $this->hasMore = $this->items->count() > $perPage;
        $this->items = $this->items->slice(0, $perPage);
    }
    public function hasMorePages(): bool
    {
        return $this->hasMore;
    }
    public function hasMorePagesWhen(bool $hasMore = true): static
    {
        $this->hasMore = $hasMore;
        return $this;
    }
    public function toArray(): array
    {
        return ['current_page' => $this->currentPage(), 'data' => $this->items->toArray(),
            'first_page_url' => $this->url(1), 'from' => $this->firstItem(), 'next_page_url' => $this->nextPageUrl(),
            'path' => $this->path(), 'per_page' => $this->perPage(), 'prev_page_url' => $this->previousPageUrl(), 'to' => $this->lastItem()];
    }
}
