<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Phone;

final class MobileNumber
{
    public static function normalize(string $number): ?string
    {
        $number = strtr($number, array_combine(
            preg_split('//u', "\u{06F0}\u{06F1}\u{06F2}\u{06F3}\u{06F4}\u{06F5}\u{06F6}\u{06F7}\u{06F8}\u{06F9}\u{0660}\u{0661}\u{0662}\u{0663}\u{0664}\u{0665}\u{0666}\u{0667}\u{0668}\u{0669}", -1, PREG_SPLIT_NO_EMPTY),
            str_split('01234567890123456789')
        ));
        $number = preg_replace('/[\s()\-]+/u', '', $number) ?? '';
        if (preg_match('/^(?:\+98|0098|98)(9[0-9]{9})$/D', $number, $matches)) {
            return '98' . $matches[1];
        }
        if (preg_match('/^0?(9[0-9]{9})$/D', $number, $matches)) {
            return '98' . $matches[1];
        }

        return null;
    }
}
