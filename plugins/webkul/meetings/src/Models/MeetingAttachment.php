<?php

namespace Webkul\Meetings\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Webkul\Meetings\Database\Factories\MeetingAttachmentFactory;
use Webkul\Security\Models\User;

class MeetingAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'creator_id',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (MeetingAttachment $attachment): void {
            $attachment->creator_id ??= Auth::id();
        });
    }

    protected static function newFactory(): MeetingAttachmentFactory
    {
        return MeetingAttachmentFactory::new();
    }
}
