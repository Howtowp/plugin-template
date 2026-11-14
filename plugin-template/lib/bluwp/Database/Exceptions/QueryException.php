<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Exceptions;

final class QueryException extends \RuntimeException
{
    public function __construct(
        private string $sql,
        private array $bindings,
        private string $databaseError,
        private int $driverCode = 0,
        private string $sqlState = ''
    ) {
        parent::__construct('Database operation failed. Inspect getDatabaseError() in a private log.');
    }

    public function getSql(): string
    {
        return $this->sql;
    }
    public function getBindings(): array
    {
        return $this->bindings;
    }
    public function getDatabaseError(): string
    {
        return $this->databaseError;
    }
    public function getDriverCode(): int
    {
        return $this->driverCode;
    }
    public function getSqlState(): string
    {
        return $this->sqlState;
    }
    public function isUniqueConstraintViolation(): bool
    {
        return $this->driverCode === 1062;
    }
    public function isDeadlock(): bool
    {
        return $this->driverCode === 1213 || $this->sqlState === '40001';
    }
}
