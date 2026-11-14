<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\Bots\Contracts;

interface ContactNumberNormalizer
{
    public function normalize(string $number): ?string;
}
