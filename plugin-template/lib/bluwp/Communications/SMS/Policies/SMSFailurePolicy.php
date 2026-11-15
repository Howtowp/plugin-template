<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS\Policies;

use Forouzandea\Bluwp\Communications\Enums\MessageStatus;
use Forouzandea\Bluwp\Communications\SMS\Data\SMSFailureType;

/** Only documented, definite rejections may be retried automatically. */
final class SMSFailurePolicy
{
    public static function type(string $gateway, string $status, ?string $code, ?int $httpStatus): ?string
    {
        return self::classify($gateway, $status, $code, $httpStatus)?->value;
    }

    public static function classify(string $gateway, MessageStatus|string $status, ?string $code, ?int $httpStatus): ?SMSFailureType
    {
        $status = is_string($status) ? MessageStatus::from($status) : $status;
        if ($status === MessageStatus::Accepted) {
            return null;
        }
        if ($status === MessageStatus::Unknown) {
            return SMSFailureType::Uncertain;
        }
        if (in_array($code, ['invalid_configuration', 'invalid_message', 'invalid_recipient', 'empty_message'], true)) {
            return SMSFailureType::Permanent;
        }
        $temporary = match ($gateway) {
            'smsir' => $httpStatus === 429 && preg_match('/^provider_-?[0-9]+$/D', $code ?? '') === 1,
            'kavenegar' => in_array($code, ['provider_409', 'provider_451'], true)
                && ($httpStatus === 200 || $httpStatus === (int) substr($code, 9)),
            'melipayamak' => $code === 'provider_6' && $httpStatus === 200,
            default => false,
        };
        if ($temporary) {
            return SMSFailureType::Temporary;
        }
        $permanent = match ($gateway) {
            'kavenegar' => ['401','403','400','404','405','411','412','413','414','415','416','417','418','419','420','422','424','426','427','428','431','432'],
            'melipayamak' => ['-111','-110','-109','-108','-10','-7','-6','-5','-4','-3','-2','-1','0','2','3','4','5','7','9','10','12','14','15','16','17','18','35','108','109','110'],
            default => [],
        };
        return in_array($code, array_map(static fn (string $value): string => 'provider_' . $value, $permanent), true)
            ? SMSFailureType::Permanent : SMSFailureType::Unclassified;
    }

    /** A null delay disables retry, including an impractically large Retry-After. */
    public static function delay(array $headers, int $now): ?int
    {
        $values = [];
        foreach ($headers as $name => $value) {
            if (strtolower((string) $name) === 'retry-after') {
                foreach ((array) $value as $entry) {
                    $values[] = $entry;
                }
            }
        }
        if (count($values) !== 1 || !is_string($values[0])) {
            return 0;
        }
        $value = trim($values[0]);
        if (preg_match('/^[0-9]+$/D', $value)) {
            $seconds = (float) $value;
            return $seconds <= 604800 ? (int) $seconds : null;
        }
        $date = \DateTimeImmutable::createFromFormat('!D, d M Y H:i:s \G\M\T', $value, new \DateTimeZone('UTC'));
        if ($date === false || $date->format('D, d M Y H:i:s \G\M\T') !== $value) {
            return 0;
        }
        $seconds = max(0, $date->getTimestamp() - $now);
        return $seconds <= 604800 ? $seconds : null;
    }
}
