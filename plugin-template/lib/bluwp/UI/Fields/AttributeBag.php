<?php

declare(strict_types=1);
/**
 * Field attribute collection.
 *
 * @package Forouzandea\Bluwp\UI\Fields
 */

namespace Forouzandea\Bluwp\UI\Fields;

defined('ABSPATH') || exit;

class AttributeBag
{
    /**
     * @var array<string, mixed>
     */
    private array $attributes = [];

    /**
     * @param mixed $value
     */
    public function set(string $key, $value): self
    {
        $this->attributes[ $key ] = $value;

        return $this;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function merge(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $this->set((string) $key, $value);
        }

        return $this;
    }

    /**
     * @return mixed|null
     */
    public function get(string $key)
    {
        return $this->attributes[ $key ] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->attributes;
    }
}
