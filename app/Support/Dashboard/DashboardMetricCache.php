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

    /**
     * @param  array<string, mixed>  $filters
     */
    public static function rememberWithFilters(string $key, array $filters, Closure $callback, int $seconds = 300): mixed
    {
        $filterHash = md5(json_encode($filters));

        return self::remember("{$key}.{$filterHash}", $callback, $seconds);
    }
}
