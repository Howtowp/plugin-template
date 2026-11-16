<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\Client\Validation;

final class PublicHttpsUrl
{
    public static function assertValid(string $url, array $allowedPorts = [443]): void
    {
        self::assertPorts($allowedPorts);

        $parts = parse_url($url);
        if (!is_array($parts) || filter_var($url, FILTER_VALIDATE_URL) === false
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || !is_string($parts['host'] ?? null)
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) {
            throw new \DomainException(__('A public HTTPS URL is required.', BLUWP), 1);
        }

        $host = strtolower(rtrim(trim($parts['host'], '[]'), '.'));
        if ($host === '' || $host === 'localhost' || preg_match('/\.(?:local|localhost|test|invalid)$/iD', $host)
            || (filter_var($host, FILTER_VALIDATE_IP)
                && !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))) {
            throw new \DomainException(__('A public HTTPS URL is required.', BLUWP), 1);
        }

        if (isset($parts['port']) && !in_array($parts['port'], $allowedPorts, true)) {
            throw new \DomainException(__('The URL uses an unsupported port.', BLUWP), 2);
        }
    }

    private static function assertPorts(array $ports): void
    {
        if ($ports === []) {
            throw new \InvalidArgumentException(__('At least one allowed HTTPS port is required.', BLUWP));
        }
        foreach ($ports as $port) {
            if (!is_int($port) || $port < 1 || $port > 65535) {
                throw new \InvalidArgumentException(__('Allowed ports must be integers between 1 and 65535.', BLUWP));
            }
        }
    }
}
