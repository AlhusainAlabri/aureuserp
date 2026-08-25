<?php

namespace App\Support;

use Carbon\Carbon;

class OmrFormatter
{
    public static function symbol(): string
    {
        return app()->getLocale() === 'ar' ? 'ر.ع.' : 'OMR';
    }

    public static function format(float|string|null $amount): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }

        return self::symbol().' '.number_format((float) $amount, 3);
    }

    public static function formatNonZero(float|string|null $amount): string
    {
        if ($amount === null || $amount === '' || (float) $amount == 0) {
            return '';
        }

        return self::format($amount);
    }

    public static function formatNumeric(float|string|null $amount): string
    {
        if ($amount === null || $amount === '') {
            return '0.000';
        }

        return number_format((float) $amount, 3);
    }

    public static function localizedDate(Carbon|string $date, string $format = 'j F Y'): string
    {
        $parsed = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $parsed->locale(app()->getLocale())->translatedFormat($format);
    }

    public static function isRtl(): bool
    {
        return app()->getLocale() === 'ar';
    }
}
