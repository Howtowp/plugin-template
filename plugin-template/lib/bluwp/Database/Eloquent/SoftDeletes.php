<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Eloquent;

use Forouzandea\Bluwp\Database\Query\Builder;

trait SoftDeletes
{
    public static function bootSoftDeletes(): void
    {
        $column = (new static())->getDeletedAtColumn();
        static::addGlobalScope('softDeletes', static fn (Builder $query) =>
            $query->whereNull($query->getTableName() . '.' . $column));
    }
    public function usesSoftDeletes(): bool
    {
        return true;
    }
    public function getDeletedAtColumn(): string
    {
        return 'deleted_at';
    }
    public function trashed(): bool
    {
        return $this->getAttribute($this->getDeletedAtColumn()) !== null;
    }
    public function restore(): bool
    {
        if (!$this->exists || !$this->fireEvent('restoring')) {
            return false;
        }
        $column = $this->getDeletedAtColumn();
        $previous = $this->attributes[$column] ?? null;
        $this->attributes[$column] = null;
        try {
            $saved = $this->save();
        } catch (\Throwable $error) {
            $this->attributes[$column] = $previous;
            throw $error;
        }
        if (!$saved) {
            $this->attributes[$column] = $previous;
            return false;
        }
        $this->fireEvent('restored', false);
        return true;
    }
    public function forceDelete(): bool
    {
        if (!$this->exists || !$this->fireEvent('forceDeleting') || !$this->fireEvent('deleting')) {
            return false;
        }
        $key = $this->original[$this->getKeyName()] ?? $this->getKey();
        if ($key === null) {
            throw new \LogicException('Cannot delete without a primary key.');
        }
        $this->getConnection()->delete($this->getTableName(), [$this->getKeyName() => $key]);
        $this->exists = false;
        $this->fireEvent('deleted', false);
        $this->fireEvent('forceDeleted', false);
        return true;
    }
}
