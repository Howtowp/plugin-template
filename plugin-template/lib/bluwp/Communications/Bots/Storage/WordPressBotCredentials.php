<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\Bots\Storage;

class WordPressBotCredentials
{
    public function __construct(private string $tokenOption, private string $secretOption)
    {
        if ($tokenOption === '' || $secretOption === '' || $tokenOption === $secretOption) {
            throw new \InvalidArgumentException(__('Distinct credential option names are required.', BLUWP));
        }
    }

    public function token(): string
    {
        $token = get_option($this->tokenOption, '');
        return is_string($token) ? $token : '';
    }

    public function secret(): string
    {
        $secret = get_option($this->secretOption, '');
        return is_string($secret) ? $secret : '';
    }

    public function saveToken(string $token): void
    {
        if ($token === '' || $token === $this->token()) {
            return;
        }
        $previousSecret = $this->secret();
        $secret = bin2hex(random_bytes(32));
        update_option($this->secretOption, $secret, false);
        if ($this->secret() !== $secret) {
            throw new \RuntimeException(__('Could not save the bot webhook secret.', BLUWP));
        }
        update_option($this->tokenOption, $token, false);
        if ($this->token() !== $token) {
            if ($previousSecret === '') {
                delete_option($this->secretOption);
            } else {
                update_option($this->secretOption, $previousSecret, false);
            }
            if ($this->secret() !== $previousSecret) {
                throw new \RuntimeException(__('Could not restore the bot webhook secret after a failed token save.', BLUWP));
            }
            throw new \RuntimeException(__('Could not save the bot token.', BLUWP));
        }
    }

    public function deleteToken(): void
    {
        delete_option($this->secretOption);
        delete_option($this->tokenOption);
        if (get_option($this->secretOption, null) !== null || get_option($this->tokenOption, null) !== null) {
            throw new \RuntimeException(__('Could not delete the bot credentials.', BLUWP));
        }
    }

    public function matchesSecret(mixed $candidate): bool
    {
        $secret = $this->secret();
        return $this->token() !== '' && $secret !== '' && is_string($candidate)
            && hash_equals($secret, $candidate);
    }
}
