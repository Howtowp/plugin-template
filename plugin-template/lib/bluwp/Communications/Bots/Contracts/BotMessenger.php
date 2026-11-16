<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\Bots\Contracts;

interface BotMessenger
{
    public function send(string $chatId, string $text, array $replyMarkup = []): void;
}
