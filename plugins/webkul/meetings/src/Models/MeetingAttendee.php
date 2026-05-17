<?php

namespace Webkul\Meetings\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Meetings\Database\Factories\MeetingAttendeeFactory;
use Webkul\Security\Models\User;

class MeetingAttendee extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'user_id',
        'attended',
        'role',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'attended'  => 'boolean',
            'signed_at' => 'datetime',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasSigned(): bool
    {
        return $this->signed_at !== null;
    }

    protected static function newFactory(): MeetingAttendeeFactory
    {
        return MeetingAttendeeFactory::new();
    }
}
