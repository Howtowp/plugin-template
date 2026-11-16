<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\Bots\API;

use Howtowp\Bluwp\Communications\Bots\Contracts\BotAPIClient;
use Howtowp\Bluwp\Communications\Bots\Enums\BotErrorReason;
use Howtowp\Bluwp\Communications\Bots\Exceptions\BotAPIException;
use Howtowp\Bluwp\Communications\Bots\Validation\BotToken;
use Howtowp\Bluwp\Http\Client\Contracts\HttpClient;
use Howtowp\Bluwp\Http\Client\Exceptions\HttpException;
use Howtowp\Bluwp\Http\Client\HttpRequest;

abstract class AbstractBotAPIClient implements BotAPIClient
{
    private const METHODS = ['setWebhook', 'deleteWebhook', 'getWebhookInfo', 'sendMessage'];

    public function __construct(private HttpClient $http)
    {
    }

    final public function call(string $token, string $method, array $parameters = [], string $baseUrl = ''): mixed
    {
        if (!BotToken::isValid($token)) {
            throw new \InvalidArgumentException(__('Invalid bot token.', BLUWP));
        }
        if (!in_array($method, self::METHODS, true)) {
            throw new \InvalidArgumentException(__('Unsupported bot API method.', BLUWP));
        }

        try {
            $response = $this->http->send(HttpRequest::postForm(
                $this->resolveBaseUrl($baseUrl) . '/bot' . $token . '/' . $method,
                $parameters,
                10
            ));
        } catch (HttpException) {
            throw new BotAPIException(BotErrorReason::Network, __('Could not connect to the bot service.', BLUWP));
        }

        $data = json_decode($response->body, true);
        if ($response->status !== 200 || !is_array($data) || ($data['ok'] ?? false) !== true) {
            $description = is_array($data) && is_string($data['description'] ?? null)
                ? trim(strip_tags($data['description'])) : '';
            $description = str_replace($token, '[redacted]', $description);
            throw new BotAPIException(
                $description !== '' ? BotErrorReason::Remote : BotErrorReason::InvalidResponse,
                $description !== '' ? $description : __('The bot service could not complete the request.', BLUWP)
            );
        }

        return $data['result'] ?? null;
    }

    abstract protected function resolveBaseUrl(string $baseUrl): string;
}
