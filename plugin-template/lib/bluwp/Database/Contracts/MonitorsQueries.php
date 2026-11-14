<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Contracts;

interface MonitorsQueries
{
    public function listen(callable $callback): void;
    public function whenQueryingForLongerThan(float $milliseconds, callable $callback): void;
    public function queryStats(): array;
    public function resetQueryStats(): void;
    public function clearQueryListeners(): void;
}
