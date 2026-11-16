<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation;

final class ValidationResult
{
    /** @param array<string, string[]> $errors */
    public function __construct(private array $errors, private array $validated)
    {
    }
    public function passes(): bool
    {
        return $this->errors === [];
    }
    public function fails(): bool
    {
        return !$this->passes();
    }
    public function errors(): array
    {
        return $this->errors;
    }
    public function first(?string $attribute = null): ?string
    {
        if ($attribute !== null) {
            return $this->errors[$attribute][0] ?? null;
        }
        $first = reset($this->errors);
        return is_array($first) ? ($first[0] ?? null) : null;
    }
    public function validated(): array
    {
        return $this->passes() ? $this->validated : [];
    }
}
