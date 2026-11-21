<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Helper;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;

final class Date
{
    private const PERSIAN_DIGITS = [
        '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
        '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
    ];

    /**
     * Jalali month name. English is the source; fa_IR lives in lang/bluwp-fa_IR.po.
     */
    private static function monthName(int $month): string
    {
        return match ($month) {
            1 => function_exists('_x') ? _x('Farvardin', 'Jalali month', 'bluwp') : 'Farvardin',
            2 => function_exists('_x') ? _x('Ordibehesht', 'Jalali month', 'bluwp') : 'Ordibehesht',
            3 => function_exists('_x') ? _x('Khordad', 'Jalali month', 'bluwp') : 'Khordad',
            4 => function_exists('_x') ? _x('Tir', 'Jalali month', 'bluwp') : 'Tir',
            5 => function_exists('_x') ? _x('Mordad', 'Jalali month', 'bluwp') : 'Mordad',
            6 => function_exists('_x') ? _x('Shahrivar', 'Jalali month', 'bluwp') : 'Shahrivar',
            7 => function_exists('_x') ? _x('Mehr', 'Jalali month', 'bluwp') : 'Mehr',
            8 => function_exists('_x') ? _x('Aban', 'Jalali month', 'bluwp') : 'Aban',
            9 => function_exists('_x') ? _x('Azar', 'Jalali month', 'bluwp') : 'Azar',
            10 => function_exists('_x') ? _x('Dey', 'Jalali month', 'bluwp') : 'Dey',
            11 => function_exists('_x') ? _x('Bahman', 'Jalali month', 'bluwp') : 'Bahman',
            12 => function_exists('_x') ? _x('Esfand', 'Jalali month', 'bluwp') : 'Esfand',
            default => '',
        };
    }

    public static function getDateTime(
        DateTimeInterface|int|string $timestamp = 'now',
        string $format = 'Y-m-d H:i:s',
        string $lang = 'fa',
    ): string {
        $date = self::siteDate($timestamp);
        if ($date === null) {
            return '';
        }

        $locale = function_exists('get_locale') ? strtolower(get_locale()) : '';
        return str_starts_with($locale, 'fa')
            ? self::formatJalali($date, $format, $lang)
            : $date->format($format);
    }

    public static function getJalaliDate(
        DateTimeInterface|int|string $timestamp = '',
        string $format = 'Y/m/d H:i:s',
        string $language = 'fa',
    ): string {
        $date = self::siteDate($timestamp);
        return $date === null ? '' : self::formatJalali($date, $format, $language);
    }

    private static function siteDate(DateTimeInterface|int|string $timestamp): ?DateTimeImmutable
    {
        $utc = new DateTimeZone('UTC');
        try {
            $date = $timestamp instanceof DateTimeInterface
                ? DateTimeImmutable::createFromInterface($timestamp)
                : new DateTimeImmutable(is_numeric($timestamp) ? '@' . $timestamp : ($timestamp ?: 'now'), $utc);

            return $date->setTimezone(function_exists('wp_timezone') ? wp_timezone() : $utc);
        } catch (Exception) {
            return null;
        }
    }

    private static function formatJalali(DateTimeImmutable $date, string $format, string $language): string
    {
        [$year, $month, $day] = self::gregorianToJalali(
            (int) $date->format('Y'),
            (int) $date->format('n'),
            (int) $date->format('j'),
        );
        $calendar = [
            'Y' => sprintf('%04d', $year),
            'y' => sprintf('%02d', $year % 100),
            'm' => sprintf('%02d', $month),
            'n' => (string) $month,
            'd' => sprintf('%02d', $day),
            'j' => (string) $day,
            'F' => self::monthName($month),
            'M' => self::monthName($month),
        ];

        $result = '';
        $escaped = false;
        foreach (str_split($format) as $token) {
            if ($escaped) {
                $result .= $token;
                $escaped = false;
                continue;
            }
            if ($token === '\\') {
                $escaped = true;
                continue;
            }
            if (str_contains('OPpTeZ', $token)) {
                $result .= $date->format($token);
                continue;
            }

            $value = $calendar[$token] ?? (str_contains('gGhHisuv', $token) ? $date->format($token) : $token);
            $result .= $language === 'fa' ? strtr($value, self::PERSIAN_DIGITS) : $value;
        }

        return $result;
    }

    private static function gregorianToJalali(int $year, int $month, int $day): array
    {
        $daysBeforeMonth = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $leapYear = $month > 2 ? $year + 1 : $year;
        $days = 355666 + 365 * $year + intdiv($leapYear + 3, 4)
            - intdiv($leapYear + 99, 100) + intdiv($leapYear + 399, 400)
            + $day + $daysBeforeMonth[$month - 1];

        $jalaliYear = -1595 + 33 * intdiv($days, 12053);
        $days %= 12053;
        $jalaliYear += 4 * intdiv($days, 1461);
        $days %= 1461;
        if ($days > 365) {
            $jalaliYear += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        return $days < 186
            ? [$jalaliYear, 1 + intdiv($days, 31), 1 + $days % 31]
            : [$jalaliYear, 7 + intdiv($days - 186, 30), 1 + ($days - 186) % 30];
    }
}
