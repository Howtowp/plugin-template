<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\SMS\Gateways\SMSIr;

use Howtowp\Bluwp\Communications\SMS\Gateways\SMSIr\SMSIr;
use Howtowp\Bluwp\Support\Validation\Rules\TypeRule;

final class SMSIrSenderRule extends TypeRule
{
    public function name(): string
    {
        return 'sms_ir_sender';
    }

    protected function passes(mixed $value): bool
    {
        return is_string($value) && ($value === '' || SMSIr::isValidSender($value));
    }
}
