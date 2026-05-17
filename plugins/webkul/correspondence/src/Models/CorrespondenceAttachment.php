<?php

namespace Webkul\Correspondence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Webkul\Correspondence\Database\Factories\CorrespondenceAttachmentFactory;
use Webkul\Security\Models\User;

class CorrespondenceAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'correspondence_id',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'creator_id',
    ];

    public function correspondence(): BelongsTo
    {
        return $this->belongsTo(Correspondence::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    protected static function newFactory(): CorrespondenceAttachmentFactory
    {
        return CorrespondenceAttachmentFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (CorrespondenceAttachment $attachment): void {
            $attachment->creator_id ??= Auth::id();
        });
    }
}
