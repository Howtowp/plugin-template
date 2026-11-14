<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Support\Validation;

use Forouzandea\Bluwp\Support\Validation\Contracts\MessageProvider;

final class WordPressMessageProvider implements MessageProvider
{
    public function message(string $rule): string
    {
        return match ($rule) {
            'required' => __('The :attribute field is required.', BLUWP),
            'required_if' => __('The :attribute field is required when :other is :value.', BLUWP),
            'string' => __('The :attribute field must be a string.', BLUWP),
            'integer' => __('The :attribute field must be an integer.', BLUWP),
            'numeric' => __('The :attribute field must be numeric.', BLUWP),
            'boolean' => __('The :attribute field must be true or false.', BLUWP),
            'array' => __('The :attribute field must be an array.', BLUWP),
            'array_keys' => __('The :attribute field may contain only these keys: :keys.', BLUWP),
            'email' => __('The :attribute field must be a valid email address.', BLUWP),
            'url' => __('The :attribute field must be a valid URL.', BLUWP),
            'uuid' => __('The :attribute field must be a valid UUID.', BLUWP),
            'min' => __('The :attribute field must be at least :min.', BLUWP),
            'max' => __('The :attribute field must not be greater than :max.', BLUWP),
            'in' => __('The selected :attribute is invalid.', BLUWP),
            'not_in' => __('The selected :attribute is invalid.', BLUWP),
            'same' => __('The :attribute field must match :other.', BLUWP),
            'confirmed' => __('The :attribute confirmation does not match.', BLUWP),
            'regex' => __('The :attribute field format is invalid.', BLUWP),
            'iranian_mobile' => __('The :attribute field must be a valid Iranian mobile number.', BLUWP),
            default => __('The :attribute field is invalid.', BLUWP),
        };
    }
}
