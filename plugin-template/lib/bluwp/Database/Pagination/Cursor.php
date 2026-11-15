<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Pagination;

use Forouzandea\Bluwp\Contracts\Arrayable;

final class Cursor implements Arrayable
{
    public function __construct(private array $parameters, private bool $pointsToNextItems = true)
    {
    }
    public function parameter(string $name): mixed
    {
        return $this->parameters[$name] ?? throw new \InvalidArgumentException('Cursor is missing a non-null ordering value: ' . $name);
    }
    public function parameters(array $names): array
    {
        return array_map(fn ($name) => $this->parameter($name), $names);
    }
    public function pointsToNextItems(): bool
    {
        return $this->pointsToNextItems;
    }
    public function pointsToPreviousItems(): bool
    {
        return !$this->pointsToNextItems;
    }
    public function toArray(): array
    {
        return $this->parameters + ['_pointsToNextItems' => $this->pointsToNextItems];
    }
    public function encode(): string
    {
        return rtrim(strtr(base64_encode(json_encode($this->toArray(), JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }
    public static function fromEncoded(?string $encoded): ?self
    {
        if ($encoded === null || $encoded === '') {
            return null;
        }
        try {
            $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);
            if ($decoded === false) {
                return null;
            }
            $data = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data) || !isset($data['_pointsToNextItems']) || !is_bool($data['_pointsToNextItems'])) {
                return null;
            }
            $direction = $data['_pointsToNextItems'];
            unset($data['_pointsToNextItems']);
            foreach ($data as $value) {
                if (!is_scalar($value) && $value !== null) {
                    return null;
                }
            }
            return new self($data, $direction);
        } catch (\JsonException $e) {
            return null;
        }
    }
}
