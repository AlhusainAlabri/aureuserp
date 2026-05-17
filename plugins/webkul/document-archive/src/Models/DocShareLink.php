<?php

namespace Webkul\DocumentArchive\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Webkul\DocumentArchive\Database\Factories\DocShareLinkFactory;
use Webkul\Security\Models\User;

class DocShareLink extends Model
{
    use HasFactory;

    protected $table = 'doc_share_links';

    protected $fillable = [
        'file_id',
        'token',
        'shared_by',
        'shared_with_email',
        'view_once',
        'expires_at',
        'viewed_at',
        'view_count',
        'is_active',
    ];

    protected $casts = [
        'view_once'  => 'boolean',
        'is_active'  => 'boolean',
        'expires_at' => 'datetime',
        'viewed_at'  => 'datetime',
        'view_count' => 'integer',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(DocFile::class, 'file_id');
    }

    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        if ($this->view_once && $this->viewed_at !== null) {
            return false;
        }

        return true;
    }

    public function markAsViewed(): void
    {
        $this->forceFill([
            'viewed_at'  => now(),
            'view_count' => $this->view_count + 1,
            'is_active'  => $this->view_once ? false : $this->is_active,
        ])->save();
    }

    public function getPublicUrl(): string
    {
        $base = rtrim((string) config('document-archive.share_link_base_url', url('/share/')), '/');

        return $base.'/'.$this->token;
    }

    protected static function newFactory(): DocShareLinkFactory
    {
        return DocShareLinkFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (DocShareLink $link): void {
            $link->token ??= Str::random(64);
        });
    }
}
