<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database\Eloquent\Casts;

final class Decimal
{
    public static function normalize(mixed $value, int $scale): string
    {
        if (!function_exists('bcadd')) {
            throw new \LogicException('The decimal cast requires ext-bcmath.');
        }
        if ($scale < 0 || $scale > 30 || !preg_match('/^[+-]?\\d+(?:\\.\\d+)?$/D', (string) $value)) {
            throw new \InvalidArgumentException('Decimal expects a plain decimal string and scale 0..30.');
        }
        $half = ($scale === 0 ? '0.5' : '0.' . str_repeat('0', $scale) . '5');
        return bcadd((string) $value, str_starts_with((string) $value, '-') ? '-' . $half : $half, $scale);
    }
}
