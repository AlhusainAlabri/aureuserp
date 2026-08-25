<?php

namespace Webkul\Meetings\Support;

use Illuminate\Support\Carbon;

class MeetingDateFormatter
{
    public static function formatDate(Carbon|string|null $date): string
    {
        if ($date === null) {
            return __('meetings::meetings.common.not_available');
        }

        return Carbon::parse($date)
            ->locale(app()->getLocale())
            ->translatedFormat(app()->getLocale() === 'ar' ? 'j F Y' : 'M j, Y');
    }

    public static function formatDateTime(Carbon|string|null $date): string
    {
        if ($date === null) {
            return __('meetings::meetings.common.not_available');
        }

        return Carbon::parse($date)
            ->locale(app()->getLocale())
            ->translatedFormat(app()->getLocale() === 'ar' ? 'l، d F Y - h:i A' : 'l, d F Y - h:i A');
    }
}
