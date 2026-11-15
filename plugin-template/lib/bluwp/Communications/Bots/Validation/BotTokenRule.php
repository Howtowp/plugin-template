<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\Bots\Validation;

use Forouzandea\Bluwp\Support\Validation\Rules\TypeRule;

final class BotTokenRule extends TypeRule
{
    public function name(): string
    {
        return 'bot_token';
    }

    protected function passes(mixed $value): bool
    {
        return is_string($value) && ($value === '' || BotToken::isValid($value));
    }
}
