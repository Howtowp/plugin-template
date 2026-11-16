<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\SMS\Contracts;

use Howtowp\Bluwp\Communications\SMS\Data\SimpleSMSMessage;
use Howtowp\Bluwp\Communications\SMS\Data\SMSResult;

interface SimpleSMSGateway
{
    public function sendSimple(SimpleSMSMessage $message): SMSResult;
}
