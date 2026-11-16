<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\Bots\Numbers;

use Howtowp\Bluwp\Communications\Bots\Contracts\ContactNumberNormalizer;
use Howtowp\Bluwp\Support\Phone\MobileNumber;

final class DefaultContactNumberNormalizer implements ContactNumberNormalizer
{
    public function normalize(string $number): ?string
    {
        return MobileNumber::normalize($number);
    }
}
