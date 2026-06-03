<?php

namespace App\Support;

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
}
