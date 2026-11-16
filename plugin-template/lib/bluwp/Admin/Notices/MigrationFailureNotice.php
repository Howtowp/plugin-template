<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Admin\Notices;

use Howtowp\Bluwp\UI\Contracts\StartupNotice;
use Howtowp\Bluwp\Database\Migrations\AutomaticMigrator;
use Howtowp\Bluwp\Foundation\Application;

final class MigrationFailureNotice implements StartupNotice
{
    public function __construct(private Application $app)
    {
    }

    public function notice(): ?array
    {
        if (!current_user_can('manage_options') || !$this->app->bound(AutomaticMigrator::class)) {
            return null;
        }
        // Do not activate an unavailable or deferred migration provider for rendering.
        $failure = $this->app->get(AutomaticMigrator::class)->failure();
        return $failure === null ? null : ['type' => 'error', 'messages' => [sprintf(
            __('Database migrations could not complete. Tracking ID: %s', BLUWP),
            $failure->correlationId
        )]];
    }
}
