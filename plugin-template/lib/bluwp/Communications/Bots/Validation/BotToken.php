<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\Bots\Validation;

final class BotToken
{
    public static function isValid(string $token): bool
    {
        return preg_match('/^[0-9]+:[A-Za-z0-9_-]{20,}$/D', $token) === 1;
    }
}
