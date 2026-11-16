<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation;

use Howtowp\Bluwp\Foundation\Application;

final class Validator
{
    public static function forApplication(Application $app): ValidatorFactory
    {
        return $app->get(ValidatorFactory::class);
    }

    public static function make(array $data, array $rules, array $messages = [], array $attributes = [], ?ValidatorFactory $factory = null): ValidationEngine
    {
        return ($factory ?? ValidatorFactory::defaults())->make($data, $rules, $messages, $attributes);
    }
}
