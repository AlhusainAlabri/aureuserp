<?php

namespace App\Models\Hr;

use App\Enums\Hr\SelfAssessmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

class EmployeeSelfAssessment extends Model
{
    protected $fillable = [
        'employee_id',
        'period_year',
        'period_month',
        'employee_comments',
        'attachment_path',
        'submitted_at',
        'status',
        'manager_feedback',
        'reviewed_by',
        'reviewed_at',
        'creator_id',
    ];

    protected function casts(): array
    {
        return [
            'status'       => SelfAssessmentStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at'  => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function periodLabel(): string
    {
        return __('hr-extensions::self_assessment.period_label', [
            'month' => $this->period_month,
            'year'  => $this->period_year,
        ]);
    }

    public function attachmentTemporaryUrl(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        return Storage::disk('private')->temporaryUrl($this->attachment_path, now()->addMinutes(60));
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $assessment): void {
            $assessment->creator_id ??= Auth::id();
        });
    }
}
