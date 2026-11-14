<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS\Contracts;

use Forouzandea\Bluwp\Communications\SMS\Data\SimpleSMSMessage;
use Forouzandea\Bluwp\Communications\SMS\Data\SMSResult;

interface SimpleSMSGateway
{
    public function sendSimple(SimpleSMSMessage $message): SMSResult;
}
