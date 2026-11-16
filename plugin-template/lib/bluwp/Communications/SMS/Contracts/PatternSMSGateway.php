<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\SMS\Contracts;

use Howtowp\Bluwp\Communications\SMS\Data\PatternSMSMessage;
use Howtowp\Bluwp\Communications\SMS\Data\SMSResult;

interface PatternSMSGateway
{
    public function sendPattern(PatternSMSMessage $message): SMSResult;
}
