<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\Bots\Messages;

use Howtowp\Bluwp\Communications\Bots\Contracts\BotAPIClient;
use Howtowp\Bluwp\Communications\Bots\Contracts\BotMessenger;
use Howtowp\Bluwp\Communications\Bots\Enums\BotErrorReason;
use Howtowp\Bluwp\Communications\Bots\Exceptions\BotAPIException;

final class APIBotMessenger implements BotMessenger
{
    public function __construct(
        private BotAPIClient $api,
        private string $token,
        private string $baseUrl = ''
    ) {
    }

    public function send(string $chatId, string $text, array $replyMarkup = []): void
    {
        $parameters = ['chat_id' => $chatId, 'text' => $text];
        if ($replyMarkup !== []) {
            $parameters['reply_markup'] = json_encode($replyMarkup, JSON_THROW_ON_ERROR);
        }
        $result = $this->api->call($this->token, 'sendMessage', $parameters, $this->baseUrl);
        if (!is_array($result) || !isset($result['message_id'])) {
            throw new BotAPIException(
                BotErrorReason::InvalidResponse,
                __('The bot service did not confirm message delivery.', BLUWP)
            );
        }
    }
}
