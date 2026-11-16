<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\API\Contracts;

interface ErrorReporter
{
    /** Report internal failures without exposing credentials or request bodies. Implementations must not throw. */
    public function report(\Throwable $error): void;
}
