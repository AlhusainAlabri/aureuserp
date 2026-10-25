<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Builder;
use Webkul\Chatter\Models\Message;

/**
 * A chatter message that records a change (it has an event). Reads through this model only see those rows.
 */
class AuditEntry extends Message
{
    protected static function booted(): void
    {
        static::addGlobalScope('audit-entries', fn (Builder $query) => $query->whereNotNull($query->qualifyColumn('event')));
    }
}
