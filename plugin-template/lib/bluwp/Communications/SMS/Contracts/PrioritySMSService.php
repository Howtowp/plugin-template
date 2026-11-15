<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS\Contracts;

use Forouzandea\Bluwp\Communications\SMS\Sending\SMSPriorityBuilder;

interface PrioritySMSService
{
    public function priorities(?int $jobId = null, int $attemptNumber = 1): SMSPriorityBuilder;
}
