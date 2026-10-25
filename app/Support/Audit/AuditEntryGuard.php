<?php

namespace App\Support\Audit;

use App\Models\Audit\AuditEntry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use WeakReference;
use Webkul\Chatter\Models\Message;

/**
 * Keeps change-log rows (messages that have an event) read-only. Eloquent events are per concrete class, so the
 * refusal is registered on Message and on AuditEntry.
 */
class AuditEntryGuard
{
    /**
     * The only attributes of a log row that may change: whether somebody has read it.
     *
     * @var array<int, string>
     */
    public const MUTABLE_ATTRIBUTES = ['is_read', 'updated_at'];

    /**
     * Chatter stamps the signed-in user as causer on every update of a message; on a log row that stamp is ignored
     * (and undone afterwards) so reading a row never changes who the log says made the change.
     *
     * @var array<int, string>
     */
    public const CAUSER_ATTRIBUTES = ['causer_type', 'causer_id'];

    protected static ?WeakReference $registeredOn = null;

    public static function register(): void
    {
        $dispatcher = Model::getEventDispatcher();

        if (! $dispatcher || static::$registeredOn?->get() === $dispatcher) {
            return;
        }

        static::$registeredOn = WeakReference::create($dispatcher);

        foreach ([Message::class, AuditEntry::class] as $class) {
            $class::updating(fn (Message $message) => static::guardUpdate($message));
            $class::updated(fn (Message $message) => static::restoreCauser($message));
            $class::deleting(fn (Message $message) => static::guardDelete($message));
        }
    }

    public static function isAuditRow(Message $message): bool
    {
        return filled($message->getRawOriginal('event')) || filled($message->getAttribute('event'));
    }

    protected static function guardUpdate(Message $message): void
    {
        if (! static::isAuditRow($message)) {
            return;
        }

        $dirty = array_keys($message->getDirty());

        $others = array_diff($dirty, static::MUTABLE_ATTRIBUTES, static::CAUSER_ATTRIBUTES);
        $changesCauser = array_intersect($dirty, static::CAUSER_ATTRIBUTES) !== [];

        if ($others === [] && (! $changesCauser || in_array('is_read', $dirty, true))) {
            return;
        }

        throw new AuthorizationException(__('audit.entry_is_read_only'));
    }

    /**
     * Put the original causer back after an allowed update, with a query that fires no model events.
     */
    protected static function restoreCauser(Message $message): void
    {
        if (! static::isAuditRow($message)) {
            return;
        }

        $changes = array_intersect_key($message->getChanges(), array_flip(static::CAUSER_ATTRIBUTES));

        if ($changes === []) {
            return;
        }

        $original = [];

        foreach (array_keys($changes) as $attribute) {
            $original[$attribute] = $message->getRawOriginal($attribute);
        }

        $message->newQueryWithoutScopes()->whereKey($message->getKey())->toBase()->update($original);

        $message->forceFill($original);
    }

    protected static function guardDelete(Message $message): void
    {
        if (static::isAuditRow($message)) {
            throw new AuthorizationException(__('audit.entry_is_read_only'));
        }
    }
}
