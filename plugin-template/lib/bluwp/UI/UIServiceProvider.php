<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\UI;

use Forouzandea\Bluwp\Foundation\ServiceProvider;
use Forouzandea\Bluwp\Foundation\DeferrableProvider;
use Forouzandea\Bluwp\UI\Fields\FieldRegistry;
use Forouzandea\Bluwp\UI\Forms\FormProcessor;

final class UIServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public static function provides(): array
    {
        return [FieldRegistry::class, FormProcessor::class];
    }

    public function register(): void
    {
        if (!$this->app->has(FieldRegistry::class)) {
            $this->singleton(FieldRegistry::class, static fn () => FieldRegistry::defaults());
        }
        if (!$this->app->has(FormProcessor::class)) {
            $this->singleton(FormProcessor::class, static fn () => new FormProcessor());
        }
    }
}
