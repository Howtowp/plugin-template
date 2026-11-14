<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Support\Diagnostics;

final class PhpDiagnosticReporter implements DiagnosticReporter
{
    private \Closure $sink;

    /** @param null|callable(string): void $sink */
    public function __construct(?callable $sink = null)
    {
        $this->sink = $sink === null ? static function (string $record): void {
            error_log($record);
        } : \Closure::fromCallable($sink);
    }

    public function report(\Throwable $error, FailureContext $context): void
    {
        $type = get_class($error);
        // Anonymous class names can contain source paths; never record those names.
        if (!preg_match('/\A[a-zA-Z_][a-zA-Z0-9_\\\\]*\z/', $type)) {
            $type = 'Throwable';
        }
        $record = '[BluWP] ' . json_encode([
            'timestamp' => gmdate('c'),
            'operation' => $context->operation,
            'scope' => $context->scope,
            'code' => $context->code,
            'correlation_id' => $context->correlationId,
            'exception_type' => $type,
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        try {
            ($this->sink)($record);
        } catch (\Throwable) {
            // A failed custom sink must not replace the original operational failure.
            error_log($record);
        }
    }
}
