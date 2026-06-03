<?php

namespace Webkul\Employee\Models;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role;
use Webkul\Employee\Database\Factories\EmployeeSubmissionFactory;
use Webkul\Partner\Models\Company;

class EmployeeSubmission extends Model
{
    use HasFactory;

    protected $table = 'employees_employee_submissions';

    protected $fillable = [
        'ticket_number',
        'type',
        'subject',
        'body',
        'employee_id',
        'submitter_name',
        'is_anonymous',
        'department_id',
        'status',
        'priority',
        'attachments',
        'resolved_at',
        'closed_at',
        'company_id',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'resolved_at'  => 'datetime',
        'closed_at'    => 'datetime',
        'attachments'  => 'array',
    ];

    public function getDisplaySubmitterNameAttribute(): string
    {
        if ($this->is_anonymous || strtolower((string) $this->submitter_name) === 'anonymous') {
            return __('hr-extensions::submissions.anonymous_label');
        }

        return $this->submitter_name ?: '—';
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(EmployeeSubmissionReply::class, 'submission_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'complaint'  => 'danger',
            'suggestion' => 'info',
            'inquiry'    => 'warning',
            'feedback'   => 'teal',
            default      => 'gray',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'open'         => 'gray',
            'under_review' => 'warning',
            'resolved'     => 'success',
            'closed'       => 'gray',
            default        => 'gray',
        };
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $submission) {
            $year = now()->year;
            $last = self::whereYear('created_at', $year)->max('id') ?? 0;
            $submission->ticket_number = 'SUB-'.$year.'-'.str_pad($last + 1, 4, '0', STR_PAD_LEFT);
            $submission->status ??= 'open';
            $submission->priority ??= 'low';

            $employee = Employee::find($submission->employee_id);
            if ($employee) {
                if (! $submission->is_anonymous) {
                    $submission->submitter_name ??= $employee->name;
                }

                $submission->department_id ??= $employee->department_id;
                $submission->company_id ??= $employee->company_id;
            }
        });

        static::created(function (self $submission) {
            $hrRole = Role::where('name', 'hr_manager')->first();
            if ($hrRole) {
                foreach ($hrRole->users as $hrManager) {
                    Notification::make()
                        ->title(__('employees::filament/resources/submission.notifications.new-submission.title'))
                        ->body(__('employees::filament/resources/submission.notifications.new-submission.body', ['ticket' => $submission->ticket_number]))
                        ->info()
                        ->sendToDatabase($hrManager);
                }
            }
        });

        static::updated(function (self $submission) {
            if ($submission->isDirty('status') && $submission->status === 'resolved' && $submission->employee?->user) {
                Notification::make()
                    ->title(__('employees::filament/resources/submission.notifications.resolved.title'))
                    ->body(__('employees::filament/resources/submission.notifications.resolved.body', ['ticket' => $submission->ticket_number]))
                    ->success()
                    ->sendToDatabase($submission->employee->user);
            }
        });
    }

    protected static function newFactory(): EmployeeSubmissionFactory
    {
        return EmployeeSubmissionFactory::new();
    }
}
