<?php

namespace Webkul\Attendance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Webkul\Attendance\Casts\UtcDateTime;
use Webkul\Attendance\Database\Factories\AttendancePunchFactory;
use Webkul\Attendance\Enums\PunchDirection;
use Webkul\Attendance\Enums\PunchSource;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

class AttendancePunch extends Model
{
    use HasFactory;

    protected $table = 'attendance_punches';

    protected $fillable = [
        'employee_id',
        'punched_at',
        'source',
        'direction',
        'device_ref',
        'import_batch_id',
        'ip',
        'latitude',
        'longitude',
        'creator_id',
    ];

    protected function casts(): array
    {
        return [
            'punched_at' => UtcDateTime::class,
            'source'     => PunchSource::class,
            'direction'  => PunchDirection::class,
            'latitude'   => 'decimal:7',
            'longitude'  => 'decimal:7',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    protected static function newFactory(): AttendancePunchFactory
    {
        return AttendancePunchFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $punch): void {
            $punch->creator_id ??= Auth::id();
        });
    }
}
