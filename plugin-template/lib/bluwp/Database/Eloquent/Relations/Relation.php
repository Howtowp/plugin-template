<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database\Eloquent\Relations;

use Howtowp\Bluwp\Database\Eloquent\Model;
use Howtowp\Bluwp\Database\Eloquent\Builder;
use Howtowp\Bluwp\Database\Query\SqlCompiler;

abstract class Relation
{
    protected Builder $query;
    protected ?Builder $constrainedQuery = null;
    protected bool $applyParentConstraint;
    private static bool $constraints = true;
    public function __construct(protected Model $parent, string $related, protected string $foreignKey, protected string $localKey)
    {
        if (!is_subclass_of($related, Model::class)) {
            throw new \InvalidArgumentException('Related class must extend Model.');
        }
        SqlCompiler::identifier($foreignKey);
        SqlCompiler::identifier($localKey);
        $model = $parent->newRelatedInstance($related);
        $this->query = $model->newQuery();
        $this->applyParentConstraint = self::$constraints;
    }
    public static function noConstraints(callable $callback): mixed
    {
        $previous = self::$constraints;
        self::$constraints = false;
        try {
            return $callback();
        } finally {
            self::$constraints = $previous;
        }
    }
    protected function relatedQuery(): Builder
    {
        return clone $this->query;
    }
    public function getQuery(): Builder
    {
        return $this->query();
    }
    public function getResults(): mixed
    {
        return $this->get();
    }
    public function existenceQuery(string $parentTable): Builder
    {
        $query = $this->relatedQuery();
        if ($query->getTableName() === $parentTable) {
            throw new \LogicException('Self-relation existence queries require aliases and are not supported.');
        }
        return $query->whereColumn($query->getTableName() . '.' . $this->foreignKey, '=', $parentTable . '.' . $this->localKey);
    }
    public function __call(string $method, array $arguments): mixed
    {
        $query = $this->query();
        $result = $query->$method(...$arguments);
        return $result === $query ? $this : $result;
    }
    abstract public function query(): Builder;
    abstract public function get(): mixed;
    abstract public function eagerLoad(array $models, string $name): void;
    protected function keys(array $models, string $column): array
    {
        if (count($models) > 1 && $this->query->hasLimitOrOffset()) {
            throw new \LogicException('Eager-load limit/offset across multiple parents is not supported.');
        }
        $keys = [];
        foreach ($models as $model) {
            if (!array_key_exists($column, $model->getAttributes())) {
                throw new \LogicException('Select the relation key before eager loading: ' . $column);
            }
            $key = $model->getAttribute($column);
            if ($key !== null) {
                $keys[(string) $key] = $key;
            }
        }
        return array_values($keys);
    }
}
