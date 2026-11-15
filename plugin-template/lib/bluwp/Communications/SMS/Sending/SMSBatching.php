<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS\Sending;

/** Bounded synchronous processing groups, independent of provider wire batch limits. */
final class SMSBatching
{
    public const DEFAULT_SIZE = 100;
    public const MAX_SIZE = 100;

    public static function validateSize(int $size): int
    {
        if ($size < 1 || $size > self::MAX_SIZE) {
            throw new \InvalidArgumentException(sprintf(__('SMS batch size must be between 1 and %d.', BLUWP), self::MAX_SIZE));
        }
        return $size;
    }

    /** @param list<string> $recipients @return \Generator<int, list<string>> */
    public static function recipients(array $recipients, int $size): \Generator
    {
        self::validateSize($size);
        $count = count($recipients);
        for ($offset = 0; $offset < $count; $offset += $size) {
            yield array_slice($recipients, $offset, $size);
        }
    }
}
