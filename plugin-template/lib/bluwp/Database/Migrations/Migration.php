<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database\Migrations;

use Howtowp\Bluwp\Database\Connection\ConnectionInterface;
use Howtowp\Bluwp\Database\Schema\Builder as Schema;
use Howtowp\Bluwp\Database\Query\Builder;

abstract class Migration
{
    private ?ConnectionInterface $connection = null;
    protected int $priority = 0;
    public function getPriority(): int
    {
        return $this->priority;
    }
    final public function useConnection(ConnectionInterface $connection): void
    {
        $this->connection = $connection;
    }
    protected function connection(): ConnectionInterface
    {
        return $this->connection ?? throw new \LogicException('Migration has no connection.');
    }
    protected function schema(): Schema
    {
        return new Schema($this->connection());
    }
    protected function table(string $table): Builder
    {
        return new Builder($this->connection(), $this->connection()->getPrefix() . $table);
    }
    abstract public function up(): void;
    abstract public function down(): void;
}
