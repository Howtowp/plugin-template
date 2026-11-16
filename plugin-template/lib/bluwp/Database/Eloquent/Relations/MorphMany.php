<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database\Eloquent\Relations;

use Howtowp\Bluwp\Database\Eloquent\Model;

class MorphMany extends HasMany
{
    public function __construct(Model $parent, string $related, private string $type, string $id, string $localKey)
    {
        parent::__construct($parent, $related, $id, $localKey);
        $this->query->where($this->query->getTableName() . '.' . $type, $parent->getMorphClass());
    }
    protected function foreignAttributes(): array
    {
        return parent::foreignAttributes() + [$this->type => $this->parent->getMorphClass()];
    }
}
