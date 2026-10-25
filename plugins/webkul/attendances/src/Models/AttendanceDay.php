<?php

namespace Webkul\Attendance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Attendance\Casts\UtcDateTime;
use Webkul\Attendance\Database\Factories\AttendanceDayFactory;
use Webkul\Attendance\Enums\DayStatus;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

class AttendanceDay extends Model
{
    use HasFactory;

    protected $table = 'attendance_days';

    protected $fillable = [
        'employee_id',
        'date',
        'first_in',
        'last_out',
        'worked_minutes',
        'expected_minutes',
        'late_minutes',
        'early_leave_minutes',
        'overtime_minutes',
        'status',
        'is_manual_override',
        'override_note',
        'edited_by',
        'leave_id',
    ];

    protected function casts(): array
    {
        return [
            'date'                => 'date',
            'first_in'            => UtcDateTime::class,
            'last_out'            => UtcDateTime::class,
            'worked_minutes'      => 'integer',
            'expected_minutes'    => 'integer',
            'late_minutes'        => 'integer',
            'early_leave_minutes' => 'integer',
            'overtime_minutes'    => 'integer',
            'status'              => DayStatus::class,
            'is_manual_override'  => 'boolean',
        ];
    }

    /**
     * Archived (soft-deleted) employees keep their attendance, and it still has to be decided and corrected.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id')->withTrashed();
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    protected static function newFactory(): AttendanceDayFactory
    {
        return AttendanceDayFactory::new();
    }
}
