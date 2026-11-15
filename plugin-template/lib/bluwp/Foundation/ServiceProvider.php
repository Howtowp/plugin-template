<?php

declare(strict_types=1);
/**
 * Base service provider contract.
 *
 * @package Forouzandea\Bluwp
 */

namespace Forouzandea\Bluwp\Foundation;

defined('ABSPATH') || exit;

abstract class ServiceProvider
{
    /**
     * Shared application instance.
     *
     * @var Application
     */
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Compose service bindings. Prefer boot() for event listeners and runtime work.
     * Registration rollback covers internal maps, not external effects or object mutations.
     */
    public function register(): void
    {
    }

    /** Preserve existing bindings and services advertised by another deferred provider. */
    protected function singleton(string $key, callable $factory): void
    {
        if (!$this->app->has($key)) {
            $this->app->singleton($key, $factory);
        }
    }

    /** Bind internal coordinated services; customize them through their public extension contracts. */
    protected function exclusiveSingleton(string $key, callable $factory): void
    {
        if ($this->app->has($key)) {
            throw new \LogicException('Framework-owned service cannot be replaced: ' . $key);
        }
        $this->app->singleton($key, $factory);
    }

    /**
     * Run logic after every provider has registered.
     */
    public function boot(): void
    {
    }
}
