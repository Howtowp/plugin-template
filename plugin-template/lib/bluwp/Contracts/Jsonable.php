<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Contracts;

interface Jsonable
{
    public function toJson(int $options = 0): string;
}
