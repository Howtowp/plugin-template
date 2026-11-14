<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Http\Client;

use Forouzandea\Bluwp\Http\HttpMethod;

final class HttpRequest
{
    /** @var array<string, string> */
    public readonly array $headers;

    /** @param array<string, string> $headers */
    public function __construct(
        public readonly HttpMethod $method,
        public readonly string $url,
        public readonly string|array|null $body = null,
        array $headers = [],
        public readonly int $timeout = 15,
        public readonly int $maximumResponseBytes = 1048576,
        public readonly int $maximumRedirects = 0
    ) {
        if (filter_var($url, FILTER_VALIDATE_URL) === false
            || !in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            throw new \InvalidArgumentException(__('A valid HTTP URL is required.', BLUWP));
        }
        if ($timeout < 1 || $timeout > 300) {
            throw new \InvalidArgumentException(__('The HTTP timeout must be between 1 and 300 seconds.', BLUWP));
        }
        if ($maximumResponseBytes < 1 || $maximumResponseBytes > 10485760) {
            throw new \InvalidArgumentException(__('The HTTP response limit must be between 1 byte and 10 megabytes.', BLUWP));
        }
        if ($maximumRedirects < 0 || $maximumRedirects > 5) {
            throw new \InvalidArgumentException(__('The HTTP redirect limit must be between 0 and 5.', BLUWP));
        }
        self::assertHeaders($headers);
        $normalized = [];
        $names = [];
        foreach ($headers as $name => $value) {
            $key = strtolower($name);
            if (isset($names[$key])) {
                unset($normalized[$names[$key]]);
            }
            $names[$key] = $name;
            $normalized[$name] = $value;
        }
        $this->headers = $normalized;
    }

    /** @param array<string, scalar|array|null> $parameters */
    public static function postForm(string $url, array $parameters, int $timeout = 15): self
    {
        return new self(HttpMethod::Post, $url, $parameters, [
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ], $timeout);
    }

    /** @param array<string, string> $headers */
    public static function postRaw(string $url, string $body, array $headers = [], int $timeout = 15): self
    {
        return new self(HttpMethod::Post, $url, $body, $headers, $timeout);
    }

    /** @param array<string, string> $headers */
    public function withHeaders(array $headers): self
    {
        self::assertHeaders($headers);
        return new self(
            $this->method,
            $this->url,
            $this->body,
            array_merge($this->headers, $headers),
            $this->timeout,
            $this->maximumResponseBytes,
            $this->maximumRedirects
        );
    }

    /** @param array<string, string> $headers */
    private static function assertHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            if (!is_string($name) || !preg_match('/^[!#$%&\'*+.^_`|~0-9A-Za-z-]+$/D', $name)
                || !is_string($value) || preg_match('/[\r\n]/', $value)) {
                throw new \InvalidArgumentException(__('Invalid HTTP header.', BLUWP));
            }
        }
    }
}
