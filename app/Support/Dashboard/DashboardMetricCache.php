<?php

namespace App\Support\Dashboard;

use Closure;
use Illuminate\Support\Facades\Cache;

class DashboardMetricCache
{
    public static function remember(string $key, Closure $callback, int $seconds = 300): mixed
    {
        $userId = auth()->id() ?? 'guest';
        $locale = app()->getLocale();

        return Cache::remember(
            "dashboard.metrics.{$userId}.{$locale}.{$key}",
            $seconds,
            $callback,
        );
    }

    public static function forget(string $key): void
    {
        $userId = auth()->id() ?? 'guest';
        $locale = app()->getLocale();

        Cache::forget("dashboard.metrics.{$userId}.{$locale}.{$key}");
    }
}
