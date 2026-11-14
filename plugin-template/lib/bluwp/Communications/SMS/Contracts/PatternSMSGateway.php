<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS\Contracts;

use Forouzandea\Bluwp\Communications\SMS\Data\PatternSMSMessage;
use Forouzandea\Bluwp\Communications\SMS\Data\SMSResult;

interface PatternSMSGateway
{
    public function sendPattern(PatternSMSMessage $message): SMSResult;
}
