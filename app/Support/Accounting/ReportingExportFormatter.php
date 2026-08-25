<?php

namespace App\Support\Accounting;

use App\Support\OmrFormatter;
use Carbon\Carbon;

class ReportingExportFormatter
{
    public static function amount(float|string|null $amount): string
    {
        if ($amount === null || $amount === '' || (float) $amount == 0) {
            return '';
        }

        return OmrFormatter::format($amount);
    }

    public static function numericCell(float|string|null $amount): float|string
    {
        if ($amount === null || $amount === '' || (float) $amount == 0) {
            return '';
        }

        return round((float) $amount, 3);
    }

    public static function date(Carbon|string $date): string
    {
        return OmrFormatter::localizedDate($date, 'j M Y');
    }

    public static function periodBucket(int $period, int $bucket): string
    {
        return match ($bucket) {
            1       => '1-'.$period,
            2       => ($period + 1).'-'.($period * 2),
            3       => (($period * 2) + 1).'-'.($period * 3),
            4       => (($period * 3) + 1).'-'.($period * 4),
            default => __('accounting-extensions::reporting.older'),
        };
    }
}
