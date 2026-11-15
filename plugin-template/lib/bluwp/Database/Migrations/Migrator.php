<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Migrations;

final class Migrator
{
    public function __construct(private MigrationRepository $repository)
    {
    }
    public function status(string $path): array
    {
        $files = $this->discover($path);
        $records = $this->repository->exists() ? $this->repository->records() : [];
        $result = [];
        foreach (array_unique(array_merge(array_keys($files), array_keys($records))) as $name) {
            $record = $records[$name] ?? null;
            $result[$name] = ['status' => $record->status ?? 'pending', 'batch' => isset($record->batch) ? (int) $record->batch : null,
                'fileExists' => isset($files[$name]), 'checksumMatches' => $record && isset($files[$name]) ? hash_equals($record->checksum, $files[$name]['checksum']) : null];
        }
        return $result;
    }
    public function pretend(string $path): array
    {
        $files = $this->discover($path);
        $records = $this->repository->exists() ? $this->repository->records() : [];
        $this->assertHealthy($records, $files);
        $pending = [];
        foreach ($files as $name => $file) {
            if (!isset($records[$name])) {
                $pending[$name] = $this->load($file['file']);
            }
        }
        uksort($pending, static fn ($a, $b) => ($pending[$a]->getPriority() <=> $pending[$b]->getPriority()) ?: strcmp($a, $b));
        $result = [];
        foreach ($pending as $name => $migration) {
            $connection = new \Forouzandea\Bluwp\Database\Connection\PretendConnection($this->repository->getConnection());
            $migration->useConnection($connection);
            $migration->up();
            $result[$name] = $connection->queries();
        }
        return $result;
    }
    public function resolveFailure(string $path, string $name, string $resolution, string $expectedChecksum): void
    {
        if (!in_array($resolution, ['completed', 'retry'], true)) {
            throw new \InvalidArgumentException('Choose completed or retry after manually verifying the database.');
        }
        $this->repository->withLock(function () use ($path, $name, $resolution, $expectedChecksum): void {
            $files = $this->discover($path);
            $record = $this->repository->records()[$name] ?? null;
            if (!$record || !isset($files[$name]) || $record->status === 'completed'
                || !hash_equals($record->checksum, $expectedChecksum) || !hash_equals($files[$name]['checksum'], $expectedChecksum)) {
                throw new \LogicException('Recovery requires a failed/incomplete migration and its unchanged checksum.');
            }
            if ($resolution === 'completed') {
                $this->repository->mark($name, 'completed');
            } else {
                $this->repository->delete($name);
            }
        });
    }

    private function discover(string $path): array
    {
        if (!is_dir($path) || !is_readable($path)) {
            throw new \InvalidArgumentException('Migration directory is not readable.');
        }
        $files = glob(rtrim($path, '/\\') . '/*.php');
        if ($files === false) {
            throw new \RuntimeException('Migration discovery failed.');
        }
        sort($files, SORT_STRING);
        $result = [];
        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (strlen($name) > 191 || !preg_match('/^[A-Za-z0-9_-]+$/D', $name)) {
                throw new \InvalidArgumentException('Invalid migration filename.');
            }
            $checksum = hash_file('sha256', $file);
            if ($checksum === false) {
                throw new \RuntimeException('Cannot read migration.');
            }
            $result[$name] = ['file' => $file, 'checksum' => $checksum];
        }
        return $result;
    }
    private function load(string $file): Migration
    {
        $migration = (static fn (string $path) => require $path)($file);
        if (!$migration instanceof Migration) {
            throw new \LogicException('Migration files must return a Migration instance (prefer an anonymous class).');
        }
        $migration->useConnection($this->repository->getConnection());
        return $migration;
    }
    private function assertHealthy(array $records, array $files): void
    {
        foreach ($records as $name => $record) {
            if ($record->status !== 'completed') {
                throw new \RuntimeException('Migration needs manual recovery before continuing: ' . $name . ' (' . $record->status . ').');
            }
            if (!isset($files[$name]) || !hash_equals($record->checksum, $files[$name]['checksum'])) {
                throw new \RuntimeException('An applied migration is missing or changed: ' . $name);
            }
        }
    }
    public function run(string $path): array
    {
        return $this->repository->withLock(function () use ($path): array {
            $files = $this->discover($path);
            $this->repository->ensureTable();
            $records = $this->repository->records();
            $this->assertHealthy($records, $files);
            $pending = [];
            foreach ($files as $name => $file) {
                if (!isset($records[$name])) {
                    $pending[$name] = $this->load($file['file']);
                }
            }
            uksort($pending, static fn (string $a, string $b): int =>
                ($pending[$a]->getPriority() <=> $pending[$b]->getPriority()) ?: strcmp($a, $b));
            $batch = $this->repository->nextBatch();
            $ran = [];
            foreach ($pending as $name => $migration) {
                $this->repository->start($name, $files[$name]['checksum'], $batch);
                try {
                    $migration->up();
                    $this->repository->mark($name, 'completed');
                } catch (\Throwable $error) {
                    $this->repository->mark($name, 'failed');
                    throw $error;
                }
                $ran[] = $name;
            }
            return $ran;
        });
    }
    public function rollback(string $path): array
    {
        return $this->repository->withLock(function () use ($path): array {
            $files = $this->discover($path);
            $this->repository->ensureTable();
            $records = $this->repository->records();
            $this->assertHealthy($records, $files);
            if (!$records) {
                return [];
            }
            $batch = max(array_map(static fn (object $record): int => (int) $record->batch, $records));
            $pending = [];
            foreach (array_reverse($records, true) as $name => $record) {
                if ((int) $record->batch === $batch) {
                    $pending[$name] = $this->load($files[$name]['file']);
                }
            }
            $rolledBack = [];
            foreach ($pending as $name => $migration) {
                $this->repository->mark($name, 'rolling_back');
                try {
                    $migration->down();
                    $this->repository->delete($name);
                } catch (\Throwable $error) {
                    $this->repository->mark($name, 'rollback_failed');
                    throw $error;
                }
                $rolledBack[] = $name;
            }
            return $rolledBack;
        });
    }
}
