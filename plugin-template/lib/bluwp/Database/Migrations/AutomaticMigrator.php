<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Migrations;

use Forouzandea\Bluwp\Support\Diagnostics\DiagnosticReporter;
use Forouzandea\Bluwp\Support\Diagnostics\FailureContext;
use Forouzandea\Bluwp\Support\Diagnostics\PhpDiagnosticReporter;

final class AutomaticMigrator
{
    private string $option;
    private DiagnosticReporter $reporter;
    private ?FailureContext $failure = null;

    public function __construct(
        private Migrator $migrator,
        private string $scope,
        private string $version,
        private string $path,
        ?DiagnosticReporter $reporter = null
    ) {
        if (!preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/D', $scope) || $version === '' || $path === '') {
            throw new \InvalidArgumentException('Automatic migrations require a scope, version and directory.');
        }
        $this->option = $scope . '_migration_version';
        $this->reporter = $reporter ?? new PhpDiagnosticReporter();
    }

    public function run(bool $force = false): array
    {
        try {
            if (!$force && get_option($this->option, null) === $this->version) {
                $this->failure = null;
                return [];
            }
            $ran = $this->migrator->run($this->path);
            // Never mark a version as ready before all pending migrations succeed.
            if (!update_option($this->option, $this->version, false)
                && get_option($this->option, null) !== $this->version) {
                throw new \RuntimeException('Migration version could not be stored.');
            }
            $this->failure = null;
            return $ran;
        } catch (\Throwable $error) {
            $this->failure = new FailureContext('database.migrate', $this->scope, 'migration_failed');
            try {
                $this->reporter->report($error, $this->failure);
            } catch (\Throwable) {
                (new PhpDiagnosticReporter())->report($error, $this->failure);
            }
            throw $error;
        }
    }

    public function maybeRun(): void
    {
        try {
            $this->run();
        } catch (\Throwable) {
            // run() records the failure; expose safe state through the app, not WordPress notices.
        }
    }

    public function renderNotice(): void
    {
        // Retained for compatibility; migration errors no longer emit WordPress notices.
    }

    public function failure(): ?FailureContext
    {
        return $this->failure;
    }
}
