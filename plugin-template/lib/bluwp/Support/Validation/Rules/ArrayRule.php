<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Support\Validation\Rules;

use Forouzandea\Bluwp\Support\Validation\Attribute;
use Forouzandea\Bluwp\Support\Validation\Contracts\Rule;
use Forouzandea\Bluwp\Support\Validation\RuleViolation;
use Forouzandea\Bluwp\Support\Validation\ValidationContext;

final class ArrayRule implements Rule
{
    /** @param string[]|null $allowedKeys Null permits any keys; [] permits only an empty array. */
    public function __construct(private ?array $allowedKeys = null)
    {
        foreach ($allowedKeys ?? [] as $key) {
            if (!is_string($key) || !preg_match('/^[A-Za-z0-9_]+$/D', $key)) {
                throw new \InvalidArgumentException(__('Array keys must be nonempty literal attribute names.', BLUWP));
            }
        }
    }

    public function name(): string
    {
        return 'array';
    }

    public function validate(Attribute $attribute, ValidationContext $context): ?RuleViolation
    {
        if (!is_array($attribute->value)) {
            return new RuleViolation($this->name());
        }
        if ($this->allowedKeys !== null) {
            foreach (array_keys($attribute->value) as $key) {
                if (!in_array((string) $key, $this->allowedKeys, true)) {
                    return new RuleViolation('array_keys', ['keys' => implode(', ', $this->allowedKeys)]);
                }
            }
        }
        return null;
    }
}
