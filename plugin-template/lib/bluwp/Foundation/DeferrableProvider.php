<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Foundation;

/** Providers with service bindings only; event listeners belong to eager bootstrapping. */
interface DeferrableProvider
{
    /** @return list<class-string> */
    public static function provides(): array;
}
