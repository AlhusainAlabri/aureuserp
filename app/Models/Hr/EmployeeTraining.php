<?php

namespace App\Models\Hr;

use App\Enums\Hr\TrainingStatus;
use App\Enums\Hr\TrainingType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

class EmployeeTraining extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'course_name',
        'provider',
        'type',
        'start_date',
        'end_date',
        'duration_hours',
        'cost',
        'cost_currency',
        'certificate_path',
        'certificate_expiry_date',
        'certificate_notified_at',
        'status',
        'notes',
        'creator_id',
    ];

    protected function casts(): array
    {
        return [
            'type'                    => TrainingType::class,
            'status'                  => TrainingStatus::class,
            'start_date'              => 'date',
            'end_date'                => 'date',
            'certificate_expiry_date' => 'date',
            'certificate_notified_at' => 'date',
            'duration_hours'          => 'decimal:2',
            'cost'                    => 'decimal:3',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', TrainingStatus::Completed);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_date', '>=', now()->toDateString())
            ->whereIn('status', [TrainingStatus::Planned, TrainingStatus::InProgress]);
    }

    public function scopeWithExpiringCertificate(Builder $query, int $days = 60): Builder
    {
        return $query->whereNotNull('certificate_expiry_date')
            ->whereBetween('certificate_expiry_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }

    public function hasExpiringCertificate(): bool
    {
        if (! $this->certificate_expiry_date) {
            return false;
        }

        return $this->certificate_expiry_date->between(now(), now()->addDays(60));
    }

    public function durationFormatted(): string
    {
        if (! $this->duration_hours) {
            return __('hr-extensions::training.duration_unknown');
        }

        $hours = (float) $this->duration_hours;

        if ($hours >= 24) {
            $days = round($hours / 24, 1);

            return __('hr-extensions::training.duration_days', ['count' => $days]);
        }

        return __('hr-extensions::training.duration_hours', ['count' => number_format($hours, 2)]);
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status?->getColor() ?? 'gray';
    }

    public function certificateTemporaryUrl(): ?string
    {
        if (! $this->certificate_path) {
            return null;
        }

        return Storage::disk('private')->temporaryUrl($this->certificate_path, now()->addMinutes(60));
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $training): void {
            $training->creator_id ??= Auth::id();
        });
    }
}
