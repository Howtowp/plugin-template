<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\SMS\Contracts;

interface SMSGatewayCredit
{
    public function credit(): float;
}
