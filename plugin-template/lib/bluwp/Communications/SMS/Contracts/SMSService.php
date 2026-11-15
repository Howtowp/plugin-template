<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS\Contracts;

use Forouzandea\Bluwp\Communications\SMS\Sending\SMSBuilder;

/** Application integration supplies configuration and sending context. */
interface SMSService
{
    public function gateway(string $name, ?int $jobId = null, int $attemptNumber = 1): SMSBuilder;
}
