<?php

use App\Services\Notifications\NotificationDedupService;
use Illuminate\Support\Facades\Cache;

it('deduplicates repeated notification dispatches within the ttl window', function (): void {
    Cache::flush();

    $service = app(NotificationDedupService::class);
    $calls = 0;

    expect($service->rememberIfNotSent('tests.dedup', function () use (&$calls): void {
        $calls++;
    }))->toBeTrue()
        ->and($service->rememberIfNotSent('tests.dedup', function () use (&$calls): void {
            $calls++;
        }))->toBeFalse()
        ->and($calls)->toBe(1);
});

it('allows the same key again after ttl expires', function (): void {
    Cache::flush();

    $service = app(NotificationDedupService::class);
    $service->markSent('tests.expire', 0);

    Cache::forget('notification_dedup:'.sha1('tests.expire'));

    expect($service->wasSentRecently('tests.expire'))->toBeFalse();
});
