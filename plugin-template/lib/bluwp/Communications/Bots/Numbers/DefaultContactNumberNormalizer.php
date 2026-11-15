<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\Bots\Numbers;

use Forouzandea\Bluwp\Communications\Bots\Contracts\ContactNumberNormalizer;
use Forouzandea\Bluwp\Support\Phone\MobileNumber;

final class DefaultContactNumberNormalizer implements ContactNumberNormalizer
{
    public function normalize(string $number): ?string
    {
        return MobileNumber::normalize($number);
    }
}
