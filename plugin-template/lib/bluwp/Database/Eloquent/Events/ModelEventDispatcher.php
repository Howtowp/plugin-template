<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database\Eloquent\Events;

use Howtowp\Bluwp\Database\Eloquent\Model;

final class ModelEventDispatcher
{
    private static array $listeners = [];

    public static function listen(string $modelClass, string $event, callable $callback): void
    {
        self::$listeners[$modelClass][$event][] = $callback;
    }

    public static function dispatch(string $event, Model $model, bool $halt = true): bool
    {
        foreach (self::$listeners[$model::class][$event] ?? [] as $callback) {
            $result = $callback($model);
            if ($halt && $result === false) {
                return false;
            }
        }
        return true;
    }

    public static function flush(string $modelClass): void
    {
        unset(self::$listeners[$modelClass]);
    }
}
