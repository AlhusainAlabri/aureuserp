<?php

namespace App\Models\Operations;

use App\Enums\Operations\BackupStatus;
use App\Enums\Operations\BackupType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Webkul\Security\Models\User;

class SystemBackup extends Model
{
    protected $fillable = [
        'name',
        'type',
        'status',
        'disk',
        'path',
        'size_bytes',
        'checksum',
        'failure_reason',
        'is_automatic',
        'created_by',
        'started_at',
        'completed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type'         => BackupType::class,
            'status'       => BackupStatus::class,
            'is_automatic' => 'boolean',
            'started_at'   => 'datetime',
            'completed_at' => 'datetime',
            'metadata'     => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function existsOnDisk(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }

    public function formattedSize(): string
    {
        if ($this->size_bytes === null) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = (float) $this->size_bytes;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2).' '.$units[$unit];
    }
}
