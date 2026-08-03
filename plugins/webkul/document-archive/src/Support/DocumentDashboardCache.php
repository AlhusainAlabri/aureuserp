<?php

namespace Webkul\DocumentArchive\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

class DocumentDashboardCache
{
    public static function remember(string $key, Closure $callback, int $seconds = 300): mixed
    {
        $userId = auth()->id() ?? 'guest';
        $locale = app()->getLocale();

        return Cache::remember(
            self::versionedUserKey($userId, $locale, $key),
            $seconds,
            $callback,
        );
    }

    public static function rememberGlobal(string $key, Closure $callback, int $seconds = 300): mixed
    {
        $locale = app()->getLocale();

        return Cache::remember(
            self::versionedGlobalKey($locale, $key),
            $seconds,
            $callback,
        );
    }

    public static function forget(string $key): void
    {
        $userId = auth()->id() ?? 'guest';
        $locale = app()->getLocale();

        Cache::forget(self::versionedUserKey($userId, $locale, $key));
    }

    public static function flush(): void
    {
        self::bumpVersion();

        $locale = app()->getLocale();

        Cache::forget(self::globalKey($locale, 'stats'));
        Cache::forget(self::globalKey($locale, 'folder_file_counts'));
        Cache::forget(self::globalKey($locale, 'tag_suggestions'));
        Cache::forget(self::globalKey($locale, 'tag_color_map'));
    }

    public static function cacheVersion(): int
    {
        return (int) Cache::get(self::globalKey(app()->getLocale(), 'version'), 0);
    }

    protected static function bumpVersion(): void
    {
        $locale = app()->getLocale();
        $key = self::globalKey($locale, 'version');

        Cache::put($key, self::cacheVersion() + 1, now()->addDays(30));
    }

    protected static function versionedUserKey(int|string $userId, string $locale, string $key): string
    {
        return self::userKey($userId, $locale, $key).'.v'.self::cacheVersion();
    }

    protected static function versionedGlobalKey(string $locale, string $key): string
    {
        return self::globalKey($locale, $key).'.v'.self::cacheVersion();
    }

    protected static function userKey(int|string $userId, string $locale, string $key): string
    {
        return "document_archive.dashboard.{$userId}.{$locale}.{$key}";
    }

    protected static function globalKey(string $locale, string $key): string
    {
        return "document_archive.dashboard.global.{$locale}.{$key}";
    }
}
