<?php

namespace App\Support\PluginManager;

use Illuminate\Http\Request;

class PluginManagerUi
{
    public const QUERY_KEY = 'access';

    public const SESSION_KEY = 'plugins_ui_unlocked';

    public static function isHidden(): bool
    {
        return (bool) config('plugin-manager.hide_ui', true);
    }

    public static function blocksPluginPermission(): bool
    {
        return self::isHidden() && ! self::isUnlocked();
    }

    public static function isUnlocked(): bool
    {
        $request = request();

        if (self::queryKeyMatches($request)) {
            self::remember($request);

            return true;
        }

        return self::isRemembered($request);
    }

    public static function queryKeyMatches(Request $request): bool
    {
        $secret = (string) config('plugin-manager.access_key', '');

        if ($secret === '') {
            return false;
        }

        $provided = $request->query(self::QUERY_KEY);

        if (! is_string($provided)) {
            return false;
        }

        $provided = trim($provided);

        if ($provided === '') {
            return false;
        }

        return hash_equals($secret, $provided);
    }

    public static function remember(Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $request->session()->put(self::SESSION_KEY, true);
    }

    public static function isRemembered(Request $request): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        return (bool) $request->session()->get(self::SESSION_KEY, false);
    }

    public static function isPluginsPath(?string $url): bool
    {
        if ($url === null || $url === '') {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            $path = $url;
        }

        $path = '/'.trim($path, '/');

        return $path === '/admin/plugins' || str_starts_with($path, '/admin/plugins/');
    }
}
