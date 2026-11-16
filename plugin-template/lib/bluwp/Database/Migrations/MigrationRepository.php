<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database\Migrations;

use Howtowp\Bluwp\Database\Connection\ConnectionInterface;
use Howtowp\Bluwp\Database\Query\Builder;
use Howtowp\Bluwp\Database\Schema\Builder as Schema;
use Howtowp\Bluwp\Database\Schema\Blueprint;

final class MigrationRepository
{
    public function __construct(private ConnectionInterface $connection, private string $scope)
    {
        if (!preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/D', $scope)) {
            throw new \InvalidArgumentException('Migration scope must be a plugin slug, up to 64 characters.');
        }
    }
    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }
    public function exists(): bool
    {
        return (new Schema($this->connection))->hasTable('bluwp_migrations');
    }
    public function query(): Builder
    {
        return (new Builder($this->connection, $this->connection->getPrefix() . 'bluwp_migrations'))
            ->where('scope', $this->scope);
    }
    public function ensureTable(): void
    {
        (new Schema($this->connection))->create('bluwp_migrations', static function (Blueprint $table): void {
            $table->id();
            $table->string('scope', 64);
            $table->string('migration', 191);
            $table->char('checksum', 64);
            $table->integer('batch')->unsigned();
            $table->string('status', 20);
            $table->dateTime('ran_at')->nullable();
            $table->unique(['scope', 'migration'], 'bluwp_migration_scope_name_unique');
        }, true);
    }
    public function records(): array
    {
        $result = [];
        foreach ($this->query()->orderBy('id')->get() as $row) {
            $result[$row->migration] = $row;
        }
        return $result;
    }
    public function nextBatch(): int
    {
        $row = $this->query()->orderBy('batch', 'DESC')->first();
        return (int) ($row->batch ?? 0) + 1;
    }
    public function start(string $name, string $checksum, int $batch): void
    {
        $this->query()->insert(['scope' => $this->scope, 'migration' => $name, 'checksum' => $checksum,
            'batch' => $batch, 'status' => 'running', 'ran_at' => null]);
    }
    public function mark(string $name, string $status): void
    {
        if (!in_array($status, ['running', 'completed', 'failed', 'rolling_back', 'rollback_failed'], true)) {
            throw new \InvalidArgumentException('Invalid migration status.');
        }
        $this->query()->where('migration', $name)->update(['status' => $status, 'ran_at' => gmdate('Y-m-d H:i:s')]);
    }
    public function delete(string $name): void
    {
        $this->query()->where('migration', $name)->delete();
    }

    public function withLock(callable $callback): mixed
    {
        if ($this->connection->transactionLevel() !== 0) {
            throw new \LogicException('Migrations must run outside transactions.');
        }
        $database = $this->connection->select('SELECT DATABASE() AS name')[0]->name ?? '';
        $lock = 'bluwp_migrations_' . substr(hash('sha256', $database . ':' . $this->connection->getPrefix()), 0, 40);
        $acquired = $this->connection->select('SELECT GET_LOCK(%s, %d) AS acquired', [$lock, 0]);
        if ((int) ($acquired[0]->acquired ?? 0) !== 1) {
            throw new \RuntimeException('Another migration process is running.');
        }
        try {
            return $callback();
        } finally {
            $this->connection->select('SELECT RELEASE_LOCK(%s) AS released', [$lock]);
        }
    }
}
