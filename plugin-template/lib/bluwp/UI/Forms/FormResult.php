<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\UI\Forms;

final class FormResult
{
    public function __construct(public readonly array $data, public readonly array $errors)
    {
    }
    public function isValid(): bool
    {
        return $this->errors === [];
    }
}
