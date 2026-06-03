<?php

namespace Webkul\Payroll\Support;

use Illuminate\Support\Carbon;

class PayrollCalendar
{
    /**
     * @return array<int, string>
     */
    public static function monthOptions(): array
    {
        return collect(range(1, 12))
            ->mapWithKeys(fn (int $month): array => [
                $month => Carbon::create(null, $month, 1)
                    ->locale(app()->getLocale())
                    ->translatedFormat('F'),
            ])
            ->all();
    }

    public static function formatPeriod(int $month, int $year): string
    {
        if (app()->getLocale() === 'ar') {
            $monthName = Carbon::create($year, $month, 1)
                ->locale('ar')
                ->translatedFormat('F');

            return "{$monthName} {$year}";
        }

        return sprintf('%02d/%d', $month, $year);
    }

    public static function formatDate(Carbon|string|null $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $carbon
            ->locale(app()->getLocale())
            ->translatedFormat(app()->getLocale() === 'ar' ? 'j F Y' : 'M j, Y');
    }
}
