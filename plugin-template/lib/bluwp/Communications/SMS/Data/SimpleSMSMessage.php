<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS\Data;

final class SimpleSMSMessage extends SMSMessage
{
    public function __construct(string $recipient, public readonly string $text)
    {
        parent::__construct($recipient);
        if (trim($text) === '') {
            throw new \InvalidArgumentException(__('SMS text cannot be empty.', BLUWP));
        }
        if (!preg_match('//u', $text)) {
            throw new \InvalidArgumentException(__('SMS content must be valid UTF-8.', BLUWP));
        }
    }
}
