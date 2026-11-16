<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\API\WordPress;

use Howtowp\Bluwp\Http\API\Contracts\ErrorReporter;
use Howtowp\Bluwp\Support\Diagnostics\DiagnosticReporter;
use Howtowp\Bluwp\Support\Diagnostics\FailureContext;
use Howtowp\Bluwp\Support\Diagnostics\PhpDiagnosticReporter;

final class PhpErrorReporter implements ErrorReporter
{
    private DiagnosticReporter $reporter;

    public function __construct(?DiagnosticReporter $reporter = null, private string $scope = 'bluwp')
    {
        $this->reporter = $reporter ?? new PhpDiagnosticReporter();
    }

    public function report(\Throwable $error): void
    {
        $context = new FailureContext('http.api', $this->scope, 'api_unhandled');
        try {
            $this->reporter->report($error, $context);
        } catch (\Throwable) {
            (new PhpDiagnosticReporter())->report($error, $context);
        }
    }
}
