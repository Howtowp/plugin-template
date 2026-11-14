<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Eloquent;

use Forouzandea\Bluwp\Support\Collection as BaseCollection;

class Collection extends BaseCollection
{
    public function map(callable $callback): BaseCollection
    {
        $result = parent::map($callback);
        foreach ($result as $item) {
            if (!$item instanceof Model) {
                return $result->toBase();
            }
        }
        return $result;
    }
    public function modelKeys(): array
    {
        return array_map(static fn (Model $model) => $model->getKey(), $this->items);
    }
    public function find(mixed $key, mixed $default = null): mixed
    {
        $key = $key instanceof Model ? $key->getKey() : $key;
        if (is_array($key)) {
            return $this->filter(static fn (Model $model) => in_array($model->getKey(), $key));
        }
        return $this->first(static fn (Model $model) => $model->getKey() == $key, $default);
    }
    public function load(string|array $relations): static
    {
        $groups = [];
        foreach ($this as $model) {
            $key = $model::class . ':' . spl_object_id($model->getConnection()) . ':' . $model->getTablePrefix();
            $groups[$key][] = $model;
        }
        foreach ($groups as $models) {
            $models[0]->newQuery()->with($relations)->eagerLoadRelations(new static($models));
        }
        return $this;
    }
    public function makeHidden(array|string $attributes): static
    {
        return $this->each(static fn (Model $model) => $model->makeHidden($attributes));
    }
    public function loadMissing(string|array $relations): static
    {
        foreach ((array) $relations as $key => $value) {
            $path = is_int($key) ? $value : $key;
            $constraint = is_int($key) ? null : $value;
            [$root, $nested] = array_pad(explode('.', $path, 2), 2, null);
            $this->filter(fn (Model $model) => !$model->relationLoaded($root))->load([$root => $nested === null ? $constraint : null]);
            if ($nested !== null) {
                $children = [];
                foreach ($this as $model) {
                    $related = $model->getRelation($root);
                    foreach ($related instanceof Model ? [$related] : ($related ?? []) as $child) {
                        $children[] = $child;
                    }
                }
                (new static($children))->loadMissing([$nested => $constraint]);
            }
        }
        return $this;
    }
    public function makeVisible(array|string $attributes): static
    {
        return $this->each(static fn (Model $model) => $model->makeVisible($attributes));
    }
}
