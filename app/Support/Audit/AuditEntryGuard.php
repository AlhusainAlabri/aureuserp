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

        if (array_diff(array_keys($message->getDirty()), static::MUTABLE_ATTRIBUTES) === []) {
            return;
        }

        throw new AuthorizationException(__('audit.entry_is_read_only'));
    }

    protected static function guardDelete(Message $message): void
    {
        if (static::isAuditRow($message)) {
            throw new AuthorizationException(__('audit.entry_is_read_only'));
        }
    }
}
