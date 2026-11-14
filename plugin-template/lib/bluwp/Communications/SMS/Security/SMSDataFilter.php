<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS\Security;

/** Filters diagnostic copies only; the transport and provider parser receive original data. */
final class SMSDataFilter
{
    private array $replacements = [];

    public function __construct(array $configuration)
    {
        foreach (['api_key', 'username', 'password'] as $key) {
            $secret = $configuration[$key] ?? null;
            if (!is_string($secret) || trim($secret) === '') {
                continue;
            }
            $secret = trim($secret);
            foreach ([$secret, rawurlencode($secret), urlencode($secret), htmlspecialchars($secret, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')] as $variant) {
                $this->replacements[$variant] = self::mask($variant);
            }
        }
    }

    public function text(string $value): string
    {
        // strtr matches the longest credential first and never reinterprets replacements.
        return json_decode(json_encode(
            strtr($value, $this->replacements),
            JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE
        ), true, 512, JSON_THROW_ON_ERROR);
    }

    public function headers(array $headers): array
    {
        return $this->values($headers);
    }

    public function body(string $body, ?string $contentType = null): string
    {
        try {
            $decoded = json_decode($body, false, 64, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
            return json_encode($this->value($decoded), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\JsonException) {
            if ($contentType !== null && str_contains(strtolower($contentType), 'application/x-www-form-urlencoded')) {
                parse_str($body, $parameters);
                return http_build_query($this->values($parameters), '', '&', PHP_QUERY_RFC3986);
            }
            // Malformed/non-JSON responses remain available for diagnosis with known secrets masked.
            return $this->text($body);
        }
    }

    private function values(array $values, bool $sensitive = false): array
    {
        foreach ($values as $key => $value) {
            $values[$key] = $this->value($value, $sensitive || self::sensitiveKey((string) $key));
        }
        return $values;
    }

    private function value(mixed $value, bool $sensitive = false): mixed
    {
        if (is_array($value)) {
            return $this->values($value, $sensitive);
        }
        if (is_object($value)) {
            return (object) $this->values((array) $value, $sensitive);
        }
        if ($sensitive && is_scalar($value)) {
            return self::mask((string) $value);
        }
        return is_string($value) ? $this->text($value) : $value;
    }

    private static function sensitiveKey(string $key): bool
    {
        $key = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $key) ?? '');
        return in_array($key, ['apikey', 'xapikey', 'password', 'passwd', 'username',
            'authorization', 'proxyauthorization', 'cookie', 'setcookie', 'accesstoken',
            'refreshtoken', 'xauthtoken', 'clientsecret', 'secret'], true);
    }

    private static function mask(string $value): string
    {
        $length = preg_match_all('/./us', $value);
        return str_repeat('*', $length === false ? strlen($value) : $length);
    }
}
