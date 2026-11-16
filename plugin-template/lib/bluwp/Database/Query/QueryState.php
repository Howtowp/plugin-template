<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database\Query;

final class QueryState
{
    public array $columns = ['*'];
    public array $wheres = [];
    public array $orders = [];
    public array $groups = [];
    public array $joins = [];
    public array $havings = [];
    public ?int $limit = null;
    public ?int $offset = null;
    public bool $distinct = false;
    public ?string $lock = null;

    public function appendWhereGroup(array $wheres): void
    {
        if ($wheres !== []) {
            $this->wheres[] = ['type' => 'nested', 'wheres' => $wheres, 'boolean' => 'AND'];
        }
    }
}
