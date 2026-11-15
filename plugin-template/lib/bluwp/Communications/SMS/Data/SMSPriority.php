<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS\Data;

final class SMSPriority
{
    public function __construct(
        public readonly int $rank,
        public readonly string $gateway,
        public readonly ?string $patternCode = null,
        public readonly ?string $message = null,
        public readonly ?array $variables = null
    ) {
        if ($rank < 1 || !preg_match('/^[a-z][a-z0-9_]*$/D', $gateway)) {
            throw new \InvalidArgumentException('SMS priorities require a positive rank and a valid gateway name.');
        }
    }
}
