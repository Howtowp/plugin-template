<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Support\Diagnostics;

interface DiagnosticReporter
{
    /** Record safe operation metadata; do not expose exception messages or trace arguments. */
    public function report(\Throwable $error, FailureContext $context): void;
}
