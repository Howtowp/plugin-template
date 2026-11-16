<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database\Eloquent;

use Howtowp\Bluwp\Database\Exceptions\ModelNotFoundException as BaseModelNotFoundException;

class ModelNotFoundException extends BaseModelNotFoundException
{
    protected string $model = '';
    protected array $ids = [];
    public function setModel(string $model, array|int|string $ids = []): static
    {
        $this->model = $model;
        $this->ids = (array) $ids;
        $this->message = 'No query results for model [' . $model . '].';
        return $this;
    }
    public function getModel(): string
    {
        return $this->model;
    }
    public function getIds(): array
    {
        return $this->ids;
    }
}
