<?php

namespace App\Support\Accounting;

use App\Support\Audit\AuditRecorder;
use BackedEnum;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;
use Webkul\Support\Models\Company;

/**
 * The accounting lock date of a company and the rule that decides what it blocks.
 *
 * A change is locked when the record sits on or before the lock date and is not a draft, either as it was stored
 * or as it would be saved. Drafts never lock, and no lock date (or no company) means nothing is locked.
 */
class PeriodLock
{
    public const COLUMN = 'accounting_lock_date';

    public const OVERRIDE_PERMISSION = 'post_in_locked_period';

    public const DRAFT_STATE = 'draft';

    /**
     * Lock dates read during this request, keyed by company id; a null value is a company without a lock date.
     *
     * @var array<int, CarbonImmutable|null>
     */
    protected static array $memo = [];

    public static function lockDateFor(?int $companyId): ?CarbonImmutable
    {
        if ($companyId === null) {
            return null;
        }

        if (array_key_exists($companyId, static::$memo)) {
            return static::$memo[$companyId];
        }

        $value = DB::table('companies')->where('id', $companyId)->value(static::COLUMN);

        return static::$memo[$companyId] = $value ? CarbonImmutable::parse($value)->startOfDay() : null;
    }

    /**
     * Whether a change from the original date and state to the new ones is refused by the lock.
     */
    public static function blocks(?int $companyId, mixed $originalDate, mixed $originalState, mixed $newDate, mixed $newState): bool
    {
        $lockDate = static::lockDateFor($companyId);

        if (! $lockDate) {
            return false;
        }

        return static::isLocked($lockDate, $originalDate, $originalState)
            || static::isLocked($lockDate, $newDate, $newState);
    }

    /**
     * Set or clear the lock date and record the change; a date after today is refused.
     *
     * @throws ValidationException
     */
    public static function setLockDate(int $companyId, ?CarbonInterface $date, Authenticatable $by): void
    {
        $date = $date ? CarbonImmutable::instance($date)->startOfDay() : null;

        if ($date && $date->greaterThan(CarbonImmutable::today())) {
            throw ValidationException::withMessages([
                static::COLUMN => __('audit.lock.date_in_future'),
            ]);
        }

        $old = DB::table('companies')->where('id', $companyId)->value(static::COLUMN);
        $old = $old ? CarbonImmutable::parse($old)->toDateString() : null;
        $new = $date?->toDateString();

        DB::table('companies')->where('id', $companyId)->update([static::COLUMN => $new]);

        static::forget();

        if ($old === $new) {
            return;
        }

        $company = Company::query()->find($companyId);

        if (! $company instanceof Model) {
            return;
        }

        AuditRecorder::record($company, 'lock_date_changed', [
            static::COLUMN => [
                'type'      => match (true) {
                    $old === null => 'added',
                    $new === null => 'removed',
                    default       => 'modified',
                },
                'old_value' => $old,
                'new_value' => $new,
            ],
        ], $by);
    }

    public static function forget(): void
    {
        static::$memo = [];
    }

    public static function userMayOverride(?Authenticatable $user): bool
    {
        if (! $user || ! method_exists($user, 'can')) {
            return false;
        }

        try {
            return (bool) $user->can(static::OVERRIDE_PERMISSION);
        } catch (Throwable) {
            return false;
        }
    }

    protected static function isLocked(CarbonImmutable $lockDate, mixed $date, mixed $state): bool
    {
        $date = static::toDate($date);

        if (! $date || $date->greaterThan($lockDate)) {
            return false;
        }

        return static::stateValue($state) !== static::DRAFT_STATE;
    }

    protected static function toDate(mixed $date): ?CarbonImmutable
    {
        if ($date === null || $date === '') {
            return null;
        }

        try {
            return ($date instanceof CarbonInterface ? CarbonImmutable::instance($date) : CarbonImmutable::parse($date))->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    protected static function stateValue(mixed $state): ?string
    {
        return $state instanceof BackedEnum ? (string) $state->value : ($state === null ? null : (string) $state);
    }
}
