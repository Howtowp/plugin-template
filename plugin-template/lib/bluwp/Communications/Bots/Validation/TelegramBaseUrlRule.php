<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\Bots\Validation;

use Howtowp\Bluwp\Communications\Bots\Drivers\Telegram\TelegramAPIClient;
use Howtowp\Bluwp\Support\Validation\Rules\TypeRule;

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
