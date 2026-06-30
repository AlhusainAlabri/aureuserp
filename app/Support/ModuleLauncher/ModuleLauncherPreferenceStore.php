<?php

namespace App\Support\ModuleLauncher;

use App\Models\UserModuleLauncherPreference;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Webkul\Security\Models\User;

class ModuleLauncherPreferenceStore
{
    /**
     * @return list<string>
     */
    public static function hiddenKeys(?User $user = null): array
    {
        $user ??= Auth::user();

        if (! $user instanceof User || ! Schema::hasTable('user_module_launcher_preferences')) {
            return [];
        }

        $preference = UserModuleLauncherPreference::query()
            ->where('user_id', $user->id)
            ->first();

        if ($preference === null || ! is_array($preference->hidden_item_keys)) {
            return [];
        }

        return array_values(array_filter(
            $preference->hidden_item_keys,
            fn (mixed $key): bool => is_string($key) && $key !== '',
        ));
    }

    /**
     * @param  list<string>  $allKeys
     * @return list<string>
     */
    public static function visibleKeys(array $allKeys, ?User $user = null): array
    {
        $hidden = array_flip(static::hiddenKeys($user));

        return array_values(array_filter(
            $allKeys,
            fn (string $key): bool => ! array_key_exists($key, $hidden),
        ));
    }

    /**
     * @param  list<string>  $allKeys
     * @param  list<string>  $visibleKeys
     */
    public static function syncVisibleItems(array $allKeys, array $visibleKeys, ?User $user = null): void
    {
        $user ??= Auth::user();

        if (! $user instanceof User || ! Schema::hasTable('user_module_launcher_preferences')) {
            return;
        }

        $visible = array_flip($visibleKeys);
        $hidden = array_values(array_filter(
            $allKeys,
            fn (string $key): bool => ! array_key_exists($key, $visible),
        ));

        UserModuleLauncherPreference::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['hidden_item_keys' => $hidden],
        );
    }

    public static function reset(?User $user = null): void
    {
        $user ??= Auth::user();

        if (! $user instanceof User || ! Schema::hasTable('user_module_launcher_preferences')) {
            return;
        }

        UserModuleLauncherPreference::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['hidden_item_keys' => []],
        );
    }

    public static function hiddenCount(?User $user = null): int
    {
        return count(static::hiddenKeys($user));
    }
}
