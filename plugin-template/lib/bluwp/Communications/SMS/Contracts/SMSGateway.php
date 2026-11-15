<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS\Contracts;

use Forouzandea\Bluwp\Communications\SMS\Data\SMSMessage;
use Forouzandea\Bluwp\Communications\SMS\Data\SMSResult;

/** Compatibility contract for gateways implementing the original send/credit API. */
interface SMSGateway extends SMSGatewayCredit
{
    public function send(SMSMessage $message): SMSResult;
}
