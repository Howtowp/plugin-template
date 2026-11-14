<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\Bots\Webhooks;

use Forouzandea\Bluwp\Communications\Bots\Contracts\BotAPIClient;
use Forouzandea\Bluwp\Communications\Bots\Enums\BotErrorReason;
use Forouzandea\Bluwp\Communications\Bots\Exceptions\BotAPIException;
use Forouzandea\Bluwp\Webhooks\PublicCallbackUrl;

class WebhookManager
{
    public function __construct(
        private BotAPIClient $api,
        private array $allowedPorts,
        private bool $requiresSecret = false
    ) {
    }

    public function status(string $token, string $callbackUrl, string $baseUrl = ''): array
    {
        $info = $this->api->call($token, 'getWebhookInfo', [], $baseUrl);
        if (!is_array($info) || !is_string($info['url'] ?? null)) {
            throw new BotAPIException(
                BotErrorReason::InvalidStatus,
                __('The bot service returned an invalid webhook status.', BLUWP)
            );
        }
        return [
            'registered' => $callbackUrl !== '' && hash_equals($callbackUrl, $info['url']),
            'hasWebhook' => $info['url'] !== '',
        ];
    }

    public function register(
        string $token,
        string $callbackUrl,
        ?string $secret = null,
        string $baseUrl = ''
    ): void {
        PublicCallbackUrl::assertValid($callbackUrl, $this->allowedPorts);
        $parameters = ['url' => $callbackUrl];
        if ($this->requiresSecret) {
            if (!is_string($secret) || !preg_match('/^[A-Za-z0-9_-]{1,256}$/D', $secret)) {
                throw new \InvalidArgumentException(__('Invalid webhook secret.', BLUWP));
            }
            $parameters['secret_token'] = $secret;
        }
        if ($this->api->call($token, 'setWebhook', $parameters, $baseUrl) !== true) {
            throw new BotAPIException(
                BotErrorReason::RegistrationFailed,
                __('The bot service did not confirm webhook registration.', BLUWP)
            );
        }
    }

    public function delete(string $token, string $baseUrl = ''): void
    {
        if ($this->api->call($token, 'deleteWebhook', [], $baseUrl) !== true) {
            throw new BotAPIException(
                BotErrorReason::RemovalFailed,
                __('The bot service did not confirm webhook removal.', BLUWP)
            );
        }
    }
}
