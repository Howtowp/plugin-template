<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\Bots\Contracts;

interface BotMessenger
{
    public function send(string $chatId, string $text, array $replyMarkup = []): void;
}
