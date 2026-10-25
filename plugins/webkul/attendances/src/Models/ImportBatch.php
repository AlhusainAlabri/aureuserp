<?php

namespace Webkul\Attendance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Attendance\Database\Factories\ImportBatchFactory;
use Webkul\Security\Models\User;

class ImportBatch extends Model
{
    use HasFactory;

    protected $table = 'attendance_import_batches';

    protected $fillable = [
        'file_name',
        'total_rows',
        'matched_rows',
        'duplicate_rows',
        'invalid_rows',
        'unmatched_device_ids',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_rows'           => 'integer',
            'matched_rows'         => 'integer',
            'duplicate_rows'       => 'integer',
            'invalid_rows'         => 'integer',
            'unmatched_device_ids' => 'array',
        ];
    }

    public function punches(): HasMany
    {
        return $this->hasMany(AttendancePunch::class, 'import_batch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function newFactory(): ImportBatchFactory
    {
        return ImportBatchFactory::new();
    }
}
