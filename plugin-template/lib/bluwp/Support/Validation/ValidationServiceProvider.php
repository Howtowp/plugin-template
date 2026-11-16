<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation;

use Howtowp\Bluwp\Foundation\Application;
use Howtowp\Bluwp\Foundation\ServiceProvider;
use Howtowp\Bluwp\Foundation\DeferrableProvider;
use Howtowp\Bluwp\Support\Validation\Contracts\MessageProvider;

final class ValidationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public static function provides(): array
    {
        return [MessageProvider::class, RuleRegistry::class, ValidatorFactory::class];
    }

    public function register(): void
    {
        if (!$this->app->has(MessageProvider::class)) {
            $this->singleton(MessageProvider::class, static fn () => new WordPressMessageProvider());
        }
        $this->singleton(RuleRegistry::class, static fn () => RuleRegistry::defaults());
        $this->singleton(ValidatorFactory::class, static fn (Application $app) => new ValidatorFactory(
            $app->get(RuleRegistry::class),
            $app->get(MessageProvider::class)
        ));
    }
}
