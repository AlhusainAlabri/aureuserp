<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Cache;

class NotificationDedupService
{
    public function wasSentRecently(string $key, int $ttlHours = 24): bool
    {
        return Cache::has($this->cacheKey($key));
    }

    public function markSent(string $key, int $ttlHours = 24): void
    {
        Cache::put($this->cacheKey($key), true, now()->addHours($ttlHours));
    }

    public function rememberIfNotSent(string $key, callable $callback, int $ttlHours = 24): bool
    {
        if ($this->wasSentRecently($key, $ttlHours)) {
            return false;
        }

        $callback();

        $this->markSent($key, $ttlHours);

        return true;
    }

    protected function cacheKey(string $key): string
    {
        return 'notification_dedup:'.sha1($key);
    }
}
