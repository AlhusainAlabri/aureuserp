<?php

namespace Webkul\Meetings\Models;

use App\Traits\HasApprovalWorkflow;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\Meetings\Database\Factories\MeetingFactory;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Project\Models\Project;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Wezlo\FilamentApproval\Models\Approval;

class Meeting extends Model
{
    use HasApprovalWorkflow, HasChatter, HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'meeting_number',
        'type',
        'status',
        'location',
        'meeting_date',
        'duration_minutes',
        'project_id',
        'company_id',
        'chair_person_id',
        'secretary_id',
        'agenda',
        'notes',
        'pdf_path',
        'creator_id',
    ];

    protected function casts(): array
    {
        return [
            'meeting_date' => 'datetime',
        ];
    }

    public function getModelTitle(): string
    {
        return __('meetings::meetings.models.meeting');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(MeetingAttendee::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(MeetingTask::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MeetingAttachment::class);
    }

    public function correspondences(): HasMany
    {
        return $this->hasMany(Correspondence::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function chairPerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chair_person_id');
    }

    public function secretary(): BelongsTo
    {
        return $this->belongsTo(User::class, 'secretary_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['draft', 'pending_approval', 'approved', 'confirmed']);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', 'archived');
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', 'confirmed');
    }

    public function canBeConfirmed(): bool
    {
        return $this->isFullyApproved();
    }

    public function isFullyApproved(): bool
    {
        return $this->isApproved();
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending_approval' => 'warning',
            'approved'         => 'info',
            'confirmed'        => 'success',
            'archived'         => 'gray',
            default            => 'gray',
        };
    }

    public function confirm(): void
    {
        if (! $this->canBeConfirmed()) {
            throw new RuntimeException(__('meetings::meetings.exceptions.confirm_before_approval'));
        }

        $this->update(['status' => 'confirmed']);
        $this->notifyAttendeesMeetingConfirmed();
    }

    public function onApprovalSubmitted(Approval $approval): void
    {
        $this->updateQuietly(['status' => 'pending_approval']);
        $this->notifyCurrentApprovers();
    }

    public function onApprovalApproved(Approval $approval): void
    {
        $this->updateQuietly(['status' => 'approved']);
        $this->notifyCreatorAndChairApproved();
    }

    public function onApprovalRejected(Approval $approval): void
    {
        $this->updateQuietly(['status' => 'draft']);
        $this->notifyCreatorRejected($approval->actions()->latest()->value('comment'));
    }

    public function notifyCurrentApprovers(): void
    {
        $users = User::query()
            ->whereIn('id', $this->currentApproval()?->currentStepInstance()?->assigned_approver_ids ?? [])
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        $this->databaseNotification('submitted_for_approval')
            ->body(__('meetings::meetings.notifications.submitted.body', [
                'number' => $this->meeting_number,
                'title'  => $this->title,
            ]))
            ->sendToDatabase($users);
    }

    public function notifyCreatorAndChairApproved(): void
    {
        $users = User::query()
            ->whereIn('id', array_filter([$this->creator_id, $this->chair_person_id]))
            ->get();

        $this->databaseNotification('fully_approved')
            ->body(__('meetings::meetings.notifications.approved.body', [
                'number' => $this->meeting_number,
            ]))
            ->sendToDatabase($users);
    }

    public function notifyCreatorRejected(?string $reason): void
    {
        if (! $this->creator) {
            return;
        }

        $this->databaseNotification('rejected')
            ->body(__('meetings::meetings.notifications.rejected.body', [
                'number' => $this->meeting_number,
                'reason' => $reason ?: __('meetings::meetings.notifications.rejected.no_reason'),
            ]))
            ->sendToDatabase($this->creator);
    }

    public function notifyAttendeesMeetingConfirmed(): void
    {
        $users = User::query()
            ->whereIn('id', $this->attendees()->pluck('user_id'))
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        $this->databaseNotification('confirmed')
            ->body(__('meetings::meetings.notifications.confirmed.body', [
                'title'    => $this->title,
                'date'     => $this->meeting_date?->translatedFormat('Y-m-d H:i'),
                'location' => $this->location ?: '-',
            ]))
            ->sendToDatabase($users);
    }

    protected function databaseNotification(string $key): Notification
    {
        return Notification::make()
            ->title(__("meetings::meetings.notifications.{$key}.title"))
            ->actions([
                Action::make('view')
                    ->label(__('meetings::meetings.actions.view'))
                    ->url(MeetingResource::getUrl('view', ['record' => $this])),
            ]);
    }

    protected static function newFactory(): MeetingFactory
    {
        return MeetingFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Meeting $meeting): void {
            $meeting->creator_id ??= Auth::id();
            $meeting->company_id ??= Auth::user()?->default_company_id;
            $meeting->meeting_number ??= static::nextMeetingNumber($meeting->meeting_date?->year ?? now()->year);
        });

        static::created(function (Meeting $meeting): void {
            $meeting->attendees()->firstOrCreate(
                ['user_id' => $meeting->chair_person_id],
                ['role' => 'chair', 'attended' => false],
            );
        });
    }

    protected static function nextMeetingNumber(int $year): string
    {
        return DB::transaction(function () use ($year): string {
            $latestNumber = static::query()
                ->whereYear('meeting_date', $year)
                ->where('meeting_number', 'like', "MTG-{$year}-%")
                ->lockForUpdate()
                ->max('meeting_number');

            $sequence = $latestNumber ? ((int) substr($latestNumber, -4)) + 1 : 1;

            return sprintf('MTG-%d-%04d', $year, $sequence);
        });
    }
}
