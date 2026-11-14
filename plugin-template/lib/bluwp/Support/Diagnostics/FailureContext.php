<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Support\Diagnostics;

final class FailureContext
{
    public readonly string $correlationId;

    public function __construct(
        public readonly string $operation,
        public readonly string $scope,
        public readonly string $code
    ) {
        foreach ([$operation, $scope, $code] as $value) {
            if (!preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9_.-]{0,63}\z/', $value)) {
                throw new \InvalidArgumentException('Diagnostic context requires bounded identifiers.');
            }
        }
        $this->correlationId = bin2hex(random_bytes(16));
    }
}
