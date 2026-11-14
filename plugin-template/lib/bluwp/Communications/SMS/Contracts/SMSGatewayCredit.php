<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS\Contracts;

interface SMSGatewayCredit
{
    public function credit(): float;
}
