<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\Bots\Validation;

use Forouzandea\Bluwp\Communications\Bots\Drivers\Telegram\TelegramAPIClient;
use Forouzandea\Bluwp\Support\Validation\Rules\TypeRule;

final class TelegramBaseUrlRule extends TypeRule
{
    public function name(): string
    {
        return 'telegram_base_url';
    }

    protected function passes(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        try {
            TelegramAPIClient::normalizeBaseUrl($value);
            return true;
        } catch (\DomainException) {
            return false;
        }
    }
}
