<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS\Data;

use Forouzandea\Bluwp\Support\Phone\MobileNumber;
use InvalidArgumentException;

/** Shared recipient contract; the factories preserve the original creation API. */
abstract class SMSMessage
{
    public readonly string $recipient;

    protected function __construct(string $recipient)
    {
        $this->recipient = MobileNumber::normalize($recipient)
            ?? throw new InvalidArgumentException(__('Invalid Iranian mobile number.', BLUWP));
    }

    public static function text(string $recipient, string $text): SimpleSMSMessage
    {
        return new SimpleSMSMessage($recipient, $text);
    }

    public static function pattern(string $recipient, string $template, array $variables): PatternSMSMessage
    {
        return new PatternSMSMessage($recipient, $template, $variables);
    }
}
