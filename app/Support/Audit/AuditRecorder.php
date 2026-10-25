<?php

namespace App\Support\Audit;

use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Throwable;
use WeakReference;
use Webkul\Chatter\Models\Message;

/**
 * Writes audit entries to Chatter's message table and listens to the models in AuditedModels.
 */
class AuditRecorder
{
    public const LOG_NAME = 'audit';

    public const MASK = '***';

    /**
     * Attributes that are never logged, because they only add noise.
     *
     * @var array<int, string>
     */
    public const IGNORED_ATTRIBUTES = ['created_at', 'updated_at', 'deleted_at', 'remember_token', 'sort'];

    /**
     * Attributes whose changes are logged without their values.
     *
     * @var array<int, string>
     */
    public const SENSITIVE_ATTRIBUTES = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'app_authentication_secret',
        'app_authentication_recovery_codes',
    ];

    /**
     * The event dispatcher the listeners were attached to; a new application has a new dispatcher and needs them again.
     */
    protected static ?WeakReference $registeredOn = null;

    protected static bool $tableReady = false;

    public static function register(): void
    {
        $dispatcher = Model::getEventDispatcher();

        if (! $dispatcher || static::$registeredOn?->get() === $dispatcher) {
            return;
        }

        static::$registeredOn = WeakReference::create($dispatcher);

        foreach (AuditedModels::classes() as $class) {
            $class::created(fn (Model $model) => static::recordModelEvent($model, 'created'));
            $class::updated(fn (Model $model) => static::recordModelEvent($model, 'updated'));
            $class::deleted(fn (Model $model) => static::recordModelEvent($model, static::deletionEvent($model)));

            if (in_array(SoftDeletes::class, class_uses_recursive($class), true)) {
                $class::restored(fn (Model $model) => static::recordModelEvent($model, 'restored'));
            }
        }
    }

    /**
     * Write one audit entry. Without an explicit causer the signed-in user is used, and nothing is written when
     * nobody is signed in. A failure is reported and never reaches the caller.
     *
     * @param  array<string, array{type: string, old_value: mixed, new_value: mixed}>  $changes
     */
    public static function record(Model $subject, string $event, array $changes, ?Authenticatable $causer = null): ?Message
    {
        try {
            $causer ??= static::signedInUser();

            if (! $causer || ! static::tableIsReady()) {
                return null;
            }

            $message = new Message;

            $message->fill([
                'type'             => 'notification',
                'log_name'         => static::LOG_NAME,
                'event'            => $event,
                'body'             => __('audit.events.'.$event, ['model' => static::modelTitle($subject)]),
                'messageable_type' => $subject->getMorphClass(),
                'messageable_id'   => $subject->getKey(),
                'causer_type'      => $causer instanceof Model ? $causer->getMorphClass() : $causer::class,
                'causer_id'        => $causer->getAuthIdentifier(),
                'company_id'       => $causer instanceof Model ? $causer->getAttribute('default_company_id') : null,
                'properties'       => $changes,
            ]);

            $message->save();

            static::restoreCauser($message, $causer);

            return $message;
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    protected static function recordModelEvent(Model $model, string $event): void
    {
        try {
            if (! static::signedInUser()) {
                return;
            }

            $changes = match ($event) {
                'created'  => static::snapshot($model, 'added'),
                'updated'  => static::updatedChanges($model),
                'restored' => [],
                default    => static::snapshot($model, 'removed'),
            };

            if ($event === 'updated' && ($changes === [] || static::isCreationEcho($model))) {
                return;
            }

            static::record($model, $event, $changes);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * A model that saves itself again while it is being created fires `updated` with every attribute as changed;
     * that is the creation, which the `created` entry already covers.
     */
    protected static function isCreationEcho(Model $model): bool
    {
        return $model->wasRecentlyCreated && array_key_exists($model->getKeyName(), $model->getChanges());
    }

    protected static function deletionEvent(Model $model): string
    {
        if (! in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            return 'deleted';
        }

        return $model->isForceDeleting() ? 'hard_deleted' : 'soft_deleted';
    }

    /**
     * @return array<string, array{type: string, old_value: mixed, new_value: mixed}>
     */
    protected static function updatedChanges(Model $model): array
    {
        $changes = [];

        foreach ($model->getChanges() as $attribute => $value) {
            if (in_array($attribute, static::IGNORED_ATTRIBUTES, true)) {
                continue;
            }

            $old = $model->getRawOriginal($attribute);

            $changes[$attribute] = [
                'type'      => $old === null ? 'added' : 'modified',
                'old_value' => static::mask($attribute, $old),
                'new_value' => static::mask($attribute, $value),
            ];
        }

        return $changes;
    }

    /**
     * @return array<string, array{type: string, old_value: mixed, new_value: mixed}>
     */
    protected static function snapshot(Model $model, string $type): array
    {
        $changes = [];

        foreach ($model->getAttributes() as $attribute => $value) {
            if (in_array($attribute, static::IGNORED_ATTRIBUTES, true) || $value === null) {
                continue;
            }

            $value = static::mask($attribute, $value);

            $changes[$attribute] = [
                'type'      => $type,
                'old_value' => $type === 'removed' ? $value : null,
                'new_value' => $type === 'removed' ? null : $value,
            ];
        }

        return $changes;
    }

    protected static function mask(string $attribute, mixed $value): mixed
    {
        return in_array($attribute, static::SENSITIVE_ATTRIBUTES, true) ? static::MASK : $value;
    }

    protected static function signedInUser(): ?Authenticatable
    {
        try {
            return Filament::auth()->user() ?? Auth::user();
        } catch (Throwable) {
            return Auth::user();
        }
    }

    protected static function modelTitle(Model $subject): string
    {
        return method_exists($subject, 'getModelTitle')
            ? (string) $subject->getModelTitle()
            : class_basename($subject);
    }

    /**
     * Chatter's Message boots once per process with whoever was signed in at that moment and stamps that user on
     * every row it creates, so put the real causer back when the two differ.
     */
    protected static function restoreCauser(Message $message, Authenticatable $causer): void
    {
        $causerId = $causer->getAuthIdentifier();

        if ((string) $message->causer_id === (string) $causerId) {
            return;
        }

        $message->forceFill([
            'causer_type' => $causer instanceof Model ? $causer->getMorphClass() : $causer::class,
            'causer_id'   => $causerId,
        ])->saveQuietly();
    }

    protected static function tableIsReady(): bool
    {
        return static::$tableReady = static::$tableReady || Schema::hasTable((new Message)->getTable());
    }
}
