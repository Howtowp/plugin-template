<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\Client;

final class HttpResponse
{
    /** @param array<string, string|string[]> $headers */
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly array $headers = []
    ) {
        if ($status < 100 || $status > 599) {
            throw new \InvalidArgumentException(__('Invalid HTTP response status.', BLUWP));
        }
    }

    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    /** Case-insensitive lookup; repeated fields remain separate values. */
    public function header(string $name): string|array|null
    {
        $values = [];
        foreach ($this->headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                $values = array_merge($values, (array) $value);
            }
        }
        return count($values) === 1 ? $values[0] : ($values === [] ? null : $values);
    }
}
