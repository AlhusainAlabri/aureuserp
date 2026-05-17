<?php

namespace Webkul\Meetings\Models;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Webkul\Meetings\Database\Factories\MeetingTaskFactory;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Purchases\Models\PurchaseOrder;
use Webkul\Security\Models\User;

class MeetingTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'title',
        'description',
        'assigned_to',
        'due_date',
        'status',
        'priority',
        'purchase_request_id',
        'completed_at',
        'creator_id',
    ];

    protected function casts(): array
    {
        return [
            'due_date'     => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignee(): BelongsTo
    {
        return $this->assignedTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_request_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereDate('due_date', '<', now()->toDateString());
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isPast()
            && $this->status !== 'completed';
    }

    public function notifyAssignedUser(): void
    {
        if (! $this->assignee) {
            return;
        }

        Notification::make()
            ->title(__('meetings::meetings.notifications.task_assigned.title'))
            ->body(__('meetings::meetings.notifications.task_assigned.body', [
                'title' => $this->title,
                'date'  => $this->due_date?->toDateString() ?: '-',
            ]))
            ->actions([
                Action::make('view')
                    ->label(__('meetings::meetings.actions.view'))
                    ->url(MeetingResource::getUrl('view', ['record' => $this->meeting])),
            ])
            ->sendToDatabase($this->assignee);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (MeetingTask $task): void {
            $task->creator_id ??= Auth::id();
        });

        static::created(function (MeetingTask $task): void {
            $task->notifyAssignedUser();
        });
    }

    protected static function newFactory(): MeetingTaskFactory
    {
        return MeetingTaskFactory::new();
    }
}
