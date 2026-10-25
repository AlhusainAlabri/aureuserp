<?php

namespace App\Support\Accounting;

use App\Exceptions\PeriodLockedException;
use App\Support\Audit\AuditRecorder;
use BackedEnum;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;
use UnitEnum;
use WeakMap;
use WeakReference;
use Webkul\Account\Models\BankStatement;
use Webkul\Account\Models\BankStatementLine;
use Webkul\Account\Models\Move;
use Webkul\Account\Models\MoveLine;
use Webkul\Account\Models\Payment;

/**
 * Refuses changes to posted accounting records that sit in a locked period (see PeriodLock for the rule).
 *
 * Eloquent fires model events per concrete class, and the plugins ship many subclasses of the guarded models
 * (invoices, bills, credit notes, refunds, journal entries, journal items, payments in the accounts, accounting,
 * invoices and purchases plugins). The guard therefore listens to the wildcard `eloquent.creating|updating|deleting`
 * events and judges every model that is an instance of a guarded base class. Wildcard listeners run after the
 * model's own listeners, so the guard sees the final attributes after the model's `saving`/`creating` hooks.
 *
 * Saving with `saveQuietly()`, `withoutEvents()` or a query-builder update bypasses model events and so the guard.
 */
class PeriodLockGuard
{
    public const CREATE = 'create';

    public const UPDATE = 'update';

    public const DELETE = 'delete';

    /**
     * Attributes a reconciliation writes on records of a locked period; a save that changes nothing else is allowed.
     * Observed while paying, part-paying and reconciling locked invoices and bills with later payments, and while an
     * older locked-period payment settled a new invoice (see PeriodLockGuardTest). `updated_at` covers `touch()`.
     * Payment `creator_id` is listed because Payment::computeCreator() stamps the current user on every save.
     *
     * @var array<class-string<Model>, array<int, string>>
     */
    public const RECONCILIATION_ATTRIBUTES = [
        Move::class              => ['payment_state', 'amount_residual', 'amount_residual_signed', 'updated_at'],
        MoveLine::class          => ['amount_residual', 'amount_residual_currency', 'reconciled', 'full_reconcile_id', 'matching_number', 'updated_at'],
        Payment::class           => ['is_reconciled', 'is_matched', 'creator_id', 'updated_at'],
        BankStatement::class     => ['updated_at'],
        BankStatementLine::class => ['updated_at'],
    ];

    /**
     * Two numbers closer than this are the same stored amount; the guarded amount columns keep four decimals.
     */
    protected const NUMERIC_TOLERANCE = 0.000001;

    protected const EVENTS = [
        'creating' => self::CREATE,
        'updating' => self::UPDATE,
        'deleting' => self::DELETE,
    ];

    protected static ?WeakReference $registeredOn = null;

    /**
     * The request the recorded overrides belong to, and the overrides already written for it.
     */
    protected static ?WeakReference $overridesRequest = null;

    /**
     * @var array<string, true>
     */
    protected static array $overridesRecorded = [];

    /**
     * Overrides of records that had no key yet, written once the record is created.
     *
     * @var WeakMap<Model, array{operation: string, lockDate: CarbonImmutable, user: Authenticatable, changes: array<string, array{old: mixed, new: mixed}>}>|null
     */
    protected static ?WeakMap $pendingOverrides = null;

    public static function register(): void
    {
        $dispatcher = Model::getEventDispatcher();

        if (! $dispatcher || static::$registeredOn?->get() === $dispatcher) {
            return;
        }

        static::$registeredOn = WeakReference::create($dispatcher);

        foreach (self::EVENTS as $event => $operation) {
            $dispatcher->listen("eloquent.{$event}: *", function (string $eventName, array $payload) use ($operation): void {
                $model = $payload[0] ?? null;

                if ($model instanceof Model && static::guardedClass($model)) {
                    static::check($model, $operation);
                }
            });
        }

        $dispatcher->listen('eloquent.created: *', function (string $eventName, array $payload): void {
            $model = $payload[0] ?? null;

            if ($model instanceof Model && static::guardedClass($model)) {
                static::recordPendingOverride($model);
            }
        });
    }

    /**
     * Refuse the operation when it touches a locked period, unless the signed-in user may override the lock.
     *
     * @throws PeriodLockedException
     */
    public static function check(Model $model, string $operation): void
    {
        $guardedClass = static::guardedClass($model);

        if (! $guardedClass) {
            return;
        }

        $lockDate = static::lockingDate($model, $operation);

        if (! $lockDate) {
            return;
        }

        $changes = [];

        if ($operation === self::UPDATE) {
            $changes = array_diff_key(static::changedAttributes($model), array_flip(self::RECONCILIATION_ATTRIBUTES[$guardedClass]));

            if ($changes === []) {
                return;
            }
        }

        $user = static::signedInUser();

        if (! PeriodLock::userMayOverride($user)) {
            throw PeriodLockedException::forLockDate($lockDate);
        }

        static::recordOverride($model, $operation, $lockDate, $user, $changes);
    }

    /**
     * Attributes whose new value really differs from the stored one. Models recompute fields on every save, so a
     * dirty attribute may hold the same value in another form (1 for "1.0000", false for 0, an enum for its value).
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public static function changedAttributes(Model $model): array
    {
        $changes = [];

        foreach ($model->getDirty() as $attribute => $value) {
            $original = $model->getRawOriginal($attribute);

            if (static::sameValue($original, $value)) {
                continue;
            }

            $changes[$attribute] = ['old' => $original, 'new' => $value];
        }

        return $changes;
    }

    /**
     * @return class-string<Model>|null
     */
    protected static function guardedClass(Model $model): ?string
    {
        foreach (array_keys(self::RECONCILIATION_ATTRIBUTES) as $class) {
            if ($model instanceof $class) {
                return $class;
            }
        }

        return null;
    }

    /**
     * The lock date that refuses this operation, or null when the record is outside every locked period.
     */
    protected static function lockingDate(Model $model, string $operation): ?CarbonImmutable
    {
        if ($model instanceof MoveLine) {
            return static::parentLockingDate($model, $operation, 'move_id', (new Move)->getTable(), 'state');
        }

        if ($model instanceof BankStatementLine) {
            return static::parentLockingDate($model, $operation, 'statement_id', (new BankStatement)->getTable(), null);
        }

        // A bank statement has no state: once it exists it counts as posted.
        $stateColumn = $model instanceof BankStatement ? null : 'state';

        foreach (static::sides($model, $operation) as $side) {
            $attributes = $side === 'original' ? $model->getRawOriginal() : $model->getAttributes();

            $lockDate = static::judge(
                $attributes['company_id'] ?? null,
                $attributes['date'] ?? null,
                $stateColumn ? ($attributes[$stateColumn] ?? null) : null,
            );

            if ($lockDate) {
                return $lockDate;
            }
        }

        return null;
    }

    /**
     * Judge a line by the stored date and state of its parent (and, when the line moves, of both parents).
     */
    protected static function parentLockingDate(Model $line, string $operation, string $foreignKey, string $parentTable, ?string $stateColumn): ?CarbonImmutable
    {
        $parentIds = [];
        $companyIds = [];

        foreach (static::sides($line, $operation) as $side) {
            $attributes = $side === 'original' ? $line->getRawOriginal() : $line->getAttributes();

            $parentIds[] = $attributes[$foreignKey] ?? null;
            $companyIds[] = $attributes['company_id'] ?? null;
        }

        $parentIds = array_values(array_unique(array_filter($parentIds, fn (mixed $id): bool => filled($id))));

        if ($parentIds === []) {
            return null;
        }

        // The line carries its parent's company; when no such company has a lock date there is nothing to look up.
        $companyIds = array_filter($companyIds, fn (mixed $id): bool => filled($id));

        if ($companyIds !== [] && ! array_filter($companyIds, fn (mixed $id): bool => PeriodLock::lockDateFor((int) $id) !== null)) {
            return null;
        }

        $columns = array_filter(['company_id', 'date', $stateColumn]);

        foreach (DB::table($parentTable)->whereIn('id', $parentIds)->get($columns) as $parent) {
            $lockDate = static::judge($parent->company_id, $parent->date, $stateColumn ? $parent->{$stateColumn} : null);

            if ($lockDate) {
                return $lockDate;
            }
        }

        return null;
    }

    /**
     * @return array<int, 'original'|'new'>
     */
    protected static function sides(Model $model, string $operation): array
    {
        return match ($operation) {
            self::CREATE => ['new'],
            self::DELETE => ['original'],
            default      => ['original', 'new'],
        };
    }

    protected static function judge(mixed $companyId, mixed $date, mixed $state): ?CarbonImmutable
    {
        $companyId = filled($companyId) ? (int) $companyId : null;

        if (! PeriodLock::blocks($companyId, $date, $state, $date, $state)) {
            return null;
        }

        return PeriodLock::lockDateFor($companyId);
    }

    protected static function sameValue(mixed $original, mixed $value): bool
    {
        $original = static::normalize($original);
        $value = static::normalize($value);

        if ($original === $value) {
            return true;
        }

        if (! is_numeric($original) || ! is_numeric($value)) {
            return false;
        }

        // Two strings without a decimal point are codes ("007" is not "7"); amounts are compared as numbers.
        if (is_string($original) && is_string($value) && ! str_contains($original.$value, '.')) {
            return false;
        }

        return abs((float) $original - (float) $value) < self::NUMERIC_TOLERANCE;
    }

    protected static function normalize(mixed $value): mixed
    {
        return match (true) {
            $value instanceof BackedEnum        => $value->value,
            $value instanceof UnitEnum          => $value->name,
            $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s'),
            is_bool($value)                     => (int) $value,
            default                             => $value,
        };
    }

    /**
     * Write one `lock_override` entry per request for each record and operation; a line is recorded on its parent.
     *
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    protected static function recordOverride(Model $model, string $operation, CarbonImmutable $lockDate, Authenticatable $user, array $changes): void
    {
        [$subject, $line] = static::overrideSubject($model, $operation);

        if (! $subject) {
            static::$pendingOverrides ??= new WeakMap;
            static::$pendingOverrides[$model] = compact('operation', 'lockDate', 'user', 'changes');

            return;
        }

        static::writeOverride($subject, $line, $operation, $lockDate, $user, $changes);
    }

    protected static function recordPendingOverride(Model $model): void
    {
        if (! static::$pendingOverrides || ! isset(static::$pendingOverrides[$model])) {
            return;
        }

        $pending = static::$pendingOverrides[$model];

        unset(static::$pendingOverrides[$model]);

        static::writeOverride($model, null, $pending['operation'], $pending['lockDate'], $pending['user'], $pending['changes']);
    }

    /**
     * The record the override is written on: the record itself, or the parent of a line. Null while a record that is
     * being created has no key yet.
     *
     * @return array{0: Model|null, 1: Model|null}
     */
    protected static function overrideSubject(Model $model, string $operation): array
    {
        [$foreignKey, $parentClass] = match (true) {
            $model instanceof MoveLine          => ['move_id', Move::class],
            $model instanceof BankStatementLine => ['statement_id', BankStatement::class],
            default                             => [null, null],
        };

        if (! $foreignKey) {
            return [$model->exists ? $model : null, null];
        }

        $parentId = $model->getAttribute($foreignKey) ?? $model->getRawOriginal($foreignKey);
        $parent = $parentId ? $parentClass::query()->withoutGlobalScopes()->find($parentId) : null;

        return [$parent ?? ($model->exists ? $model : null), $parent ? $model : null];
    }

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    protected static function writeOverride(Model $subject, ?Model $line, string $operation, CarbonImmutable $lockDate, Authenticatable $user, array $changes): void
    {
        $key = $subject->getTable().':'.$subject->getKey().':'.$operation;

        if (static::overrideAlreadyRecorded($key)) {
            return;
        }

        $properties = [
            PeriodLock::COLUMN => ['type' => 'added', 'old_value' => null, 'new_value' => $lockDate->toDateString()],
            'operation'        => ['type' => 'added', 'old_value' => null, 'new_value' => $operation],
        ];

        if ($line?->getKey()) {
            $properties['line_id'] = ['type' => 'added', 'old_value' => null, 'new_value' => $line->getKey()];
        }

        foreach ($changes as $attribute => $change) {
            $properties[$attribute] ??= [
                'type'      => $change['old'] === null ? 'added' : 'modified',
                'old_value' => static::storable($change['old']),
                'new_value' => static::storable($change['new']),
            ];
        }

        AuditRecorder::record($subject, 'lock_override', $properties, $user);
    }

    protected static function overrideAlreadyRecorded(string $key): bool
    {
        $request = app()->bound('request') ? app('request') : null;

        if (static::$overridesRequest?->get() !== $request) {
            static::$overridesRequest = $request ? WeakReference::create($request) : null;
            static::$overridesRecorded = [];
        }

        if (isset(static::$overridesRecorded[$key])) {
            return true;
        }

        static::$overridesRecorded[$key] = true;

        return false;
    }

    protected static function storable(mixed $value): mixed
    {
        $value = static::normalize($value);

        return is_scalar($value) || $value === null ? $value : json_encode($value);
    }

    protected static function signedInUser(): ?Authenticatable
    {
        try {
            return Filament::auth()->user() ?? Auth::user();
        } catch (Throwable) {
            return Auth::user();
        }
    }
}
