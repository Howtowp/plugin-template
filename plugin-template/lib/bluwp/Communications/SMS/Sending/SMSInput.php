<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS\Sending;

use Forouzandea\Bluwp\Support\Phone\MobileNumber;

final class SMSInput
{
    /** @return list<string> */
    public static function recipients(string|array $recipients): array
    {
        $normalized = [];
        foreach (is_array($recipients) ? $recipients : [$recipients] as $recipient) {
            $number = is_string($recipient) ? MobileNumber::normalize($recipient) : null;
            if ($number === null) {
                throw new \InvalidArgumentException(__('Invalid Iranian mobile number.', BLUWP));
            }
            $normalized[$number] = $number;
        }
        if ($normalized === []) {
            throw new \InvalidArgumentException(__('SMS recipients are required.', BLUWP));
        }
        return array_values($normalized);
    }

    public static function text(string $text, array $data = []): string
    {
        $compiled = preg_replace_callback('/\{([a-zA-Z][a-zA-Z0-9_]*)\}/', static function (array $match) use ($data): string {
            $value = $data[$match[1]] ?? null;
            if (!is_string($value) && !is_int($value) && !is_float($value)) {
                throw new \InvalidArgumentException(sprintf(
                    __('Missing or invalid SMS placeholder: %s', BLUWP),
                    $match[1]
                ));
            }
            return (string) $value;
        }, $text);
        if ($compiled === null) {
            throw new \InvalidArgumentException(__('Invalid SMS text.', BLUWP));
        }
        return $compiled;
    }
}
