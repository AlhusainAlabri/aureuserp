<?php

namespace Webkul\Attendance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Attendance\Database\Factories\OvertimeRequestFactory;
use Webkul\Attendance\Enums\OvertimeDayType;
use Webkul\Attendance\Enums\OvertimeStatus;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

class OvertimeRequest extends Model
{
    use HasFactory;

    protected $table = 'attendance_overtime_requests';

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $fillable = [
        'employee_id',
        'date',
        'minutes',
        'day_type',
        'multiplier',
        'status',
        'decided_by',
        'decided_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'date'       => 'date',
            'minutes'    => 'integer',
            'day_type'   => OvertimeDayType::class,
            'multiplier' => 'decimal:2',
            'status'     => OvertimeStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    /**
     * Archived (soft-deleted) employees keep their attendance, and it still has to be decided and corrected.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id')->withTrashed();
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    protected static function newFactory(): OvertimeRequestFactory
    {
        return OvertimeRequestFactory::new();
    }
}
