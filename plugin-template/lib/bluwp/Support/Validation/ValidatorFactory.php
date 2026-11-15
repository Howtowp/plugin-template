<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Support\Validation;

use Forouzandea\Bluwp\Support\Validation\Contracts\MessageProvider;

final class ValidatorFactory
{
    public function __construct(private RuleRegistry $rules, private MessageProvider $messages)
    {
    }
    public static function defaults(): self
    {
        return new self(RuleRegistry::defaults(), new WordPressMessageProvider());
    }
    public function make(array $data, array $rules, array $messages = [], array $attributes = []): ValidationEngine
    {
        return new ValidationEngine($data, $rules, new RuleParser($this->rules), $this->messages, $messages, $attributes);
    }
    public function rules(): RuleRegistry
    {
        return $this->rules;
    }
}
