<?php

namespace App\Support\Operations;

use App\Enums\Operations\BackupFrequency;
use App\Settings\OperationsSettings;
use Illuminate\Support\Facades\Cache;

class AutomaticBackupScheduler
{
    public static function shouldRunNow(OperationsSettings $settings): bool
    {
        if (! $settings->backup_enabled || $settings->backup_frequency === BackupFrequency::Disabled) {
            return false;
        }

        if (! static::isWithinBackupWindow($settings->backup_time)) {
            return false;
        }

        return match ($settings->backup_frequency) {
            BackupFrequency::Daily   => true,
            BackupFrequency::Weekly  => now()->isMonday(),
            BackupFrequency::Monthly => now()->day === 1,
            default                  => false,
        };
    }

    public static function alreadyRanForPeriod(OperationsSettings $settings): bool
    {
        return Cache::has(static::periodCacheKey($settings));
    }

    public static function markRanForPeriod(OperationsSettings $settings): void
    {
        Cache::put(
            static::periodCacheKey($settings),
            true,
            static::periodCacheExpiry($settings),
        );
    }

    protected static function isWithinBackupWindow(string $backupTime): bool
    {
        if (! preg_match('/^(\d{1,2}):(\d{2})$/', $backupTime, $matches)) {
            return now()->hour === 2 && now()->minute < 5;
        }

        $targetHour = (int) $matches[1];
        $targetMinute = (int) $matches[2];
        $now = now();

        if ($now->hour !== $targetHour) {
            return false;
        }

        return $now->minute >= $targetMinute && $now->minute < ($targetMinute + 5);
    }

    protected static function periodCacheKey(OperationsSettings $settings): string
    {
        return match ($settings->backup_frequency) {
            BackupFrequency::Weekly  => 'operations:backup:automatic:week:'.now()->format('o-W'),
            BackupFrequency::Monthly => 'operations:backup:automatic:month:'.now()->format('Y-m'),
            default                  => 'operations:backup:automatic:day:'.now()->toDateString(),
        };
    }

    protected static function periodCacheExpiry(OperationsSettings $settings): \DateTimeInterface
    {
        return match ($settings->backup_frequency) {
            BackupFrequency::Weekly  => now()->endOfWeek(),
            BackupFrequency::Monthly => now()->endOfMonth(),
            default                  => now()->endOfDay(),
        };
    }
}
