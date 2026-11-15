<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Http\API\Requests;

use Forouzandea\Bluwp\Support\Validation\ValidationResult;
use Forouzandea\Bluwp\Support\Validation\ValidatorFactory;

final class RequestValidator
{
    public function __construct(private ValidatorFactory $factory)
    {
    }

    public function validate(
        \WP_REST_Request $request,
        RequestSource $source,
        array $rules,
        array $messages = [],
        array $attributes = []
    ): ValidationResult|\WP_Error {
        $input = $source->read($request);
        if ($input instanceof \WP_Error) {
            return $input;
        }
        return $this->factory->make($input, $rules, $messages, $attributes)->validate();
    }
}
