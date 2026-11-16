<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\SMS\Contracts;

use Howtowp\Bluwp\Communications\SMS\Sending\SMSPriorityBuilder;

interface PrioritySMSService
{
    public function priorities(?int $jobId = null, int $attemptNumber = 1): SMSPriorityBuilder;
}
