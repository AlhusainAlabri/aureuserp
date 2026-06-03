<?php

namespace Webkul\MyNotes\Support;

use Illuminate\Support\Carbon;

class NoteDateFormatter
{
    public static function formatDateTime(Carbon|string|null $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $carbon
            ->locale(app()->getLocale())
            ->translatedFormat(app()->getLocale() === 'ar' ? 'j F Y · h:i A' : 'D, j M Y · h:i A');
    }

    public static function formatDate(Carbon|string|null $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $carbon
            ->locale(app()->getLocale())
            ->translatedFormat(app()->getLocale() === 'ar' ? 'j F Y' : 'D, j M Y');
    }

    public static function formatTime(Carbon|string|null $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $carbon
            ->locale(app()->getLocale())
            ->translatedFormat('h:i A');
    }
}
