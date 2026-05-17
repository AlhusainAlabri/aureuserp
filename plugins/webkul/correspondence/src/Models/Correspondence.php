<?php

namespace Webkul\Correspondence\Models;

use App\Mail\OutgoingCorrespondenceMail;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Correspondence\Database\Factories\CorrespondenceFactory;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource;
use Webkul\Meetings\Models\Meeting;
use Webkul\Project\Models\Project;
use Webkul\Purchases\Models\PurchaseOrder;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Wezlo\FilamentApproval\Models\Approval;

class Correspondence extends Model
{
    use HasApprovalWorkflow, HasChatter, HasFactory, HasLogActivity, SoftDeletes;

    protected $fillable = [
        'reference_number',
        'direction',
        'type',
        'priority',
        'subject',
        'body',
        'sender_name',
        'sender_entity',
        'from_department_id',
        'to_department_id',
        'to_user_id',
        'to_external_email',
        'status',
        'received_at',
        'sent_at',
        'due_date',
        'project_id',
        'meeting_id',
        'purchase_request_id',
        'parent_id',
        'company_id',
        'creator_id',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'date',
            'sent_at'     => 'datetime',
            'due_date'    => 'date',
        ];
    }

    public function getModelTitle(): string
    {
        return __('correspondence::correspondence.correspondence');
    }

    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_request_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->oldest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CorrespondenceAttachment::class);
    }

    public function followers(): HasMany
    {
        return $this->hasMany(CorrespondenceFollower::class);
    }

    public function scopeOutgoing(Builder $query): Builder
    {
        return $query->where('direction', 'outgoing');
    }

    public function scopeIncoming(Builder $query): Builder
    {
        return $query->where('direction', 'incoming');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    public function scopeReceived(Builder $query): Builder
    {
        return $query->where('status', 'received');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', 'archived');
    }

    public function scopeUrgent(Builder $query): Builder
    {
        return $query->where('priority', 'urgent');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['sent', 'archived']);
    }

    public function isOutgoing(): bool
    {
        return $this->direction === 'outgoing';
    }

    public function isIncoming(): bool
    {
        return $this->direction === 'incoming';
    }

    public function isOverdue(): bool
    {
        return $this->due_date?->isPast() && ! in_array($this->status, ['sent', 'archived'], true);
    }

    public function hasReplies(): bool
    {
        return $this->replies()->exists();
    }

    public function isThreadRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function getThreadDepth(): int
    {
        $depth = 0;
        $parent = $this->parent;

        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }

        return $depth;
    }

    public function canBeApproved(): bool
    {
        return $this->isOutgoing();
    }

    public function canSubmitForApproval(?int $userId = null): bool
    {
        return $this->isOutgoing() && $this->status === 'draft';
    }

    public function isFullyApproved(): bool
    {
        return $this->isApproved();
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending_approval' => 'warning',
            'approved'         => 'info',
            'sent', 'received' => 'success',
            'archived'         => 'gray',
            default            => 'gray',
        };
    }

    public function send(): void
    {
        if ($this->isOutgoing() && ! $this->isFullyApproved()) {
            throw new RuntimeException(__('correspondence::correspondence.exceptions.send_before_approval'));
        }

        try {
            if ($this->isOutgoing() && $this->type === 'external' && filled($this->to_external_email)) {
                Mail::to($this->to_external_email)->queue(new OutgoingCorrespondenceMail($this));
                Log::info('تم إرسال المراسلة بالبريد الإلكتروني', ['correspondence_id' => $this->id]);
            }

            if ($this->type === 'internal' && $this->toUser) {
                $this->databaseNotification('sent')
                    ->body(__('correspondence::correspondence.notify.sent.body', [
                        'reference' => $this->reference_number,
                        'target'    => $this->toUser->name,
                    ]))
                    ->sendToDatabase($this->toUser);
            }

            $this->update(['status' => 'sent', 'sent_at' => now()]);
            $this->notifyCreatorAndFollowers('sent');
        } catch (Throwable $exception) {
            Log::error('Correspondence mail send failed', [
                'correspondence_id' => $this->id,
                'error'             => $exception->getMessage(),
            ]);

            throw new RuntimeException(__('correspondence::correspondence.email_failed'), previous: $exception);
        }
    }

    public function createReply(array $attributes = []): self
    {
        $reply = static::query()->create([
            'parent_id'           => $this->id,
            'direction'           => $this->isIncoming() ? 'outgoing' : 'incoming',
            'type'                => $this->type === 'external' ? 'external' : 'internal',
            'priority'            => $this->priority,
            'subject'             => static::correspondenceTranslation('reply_subject', ['subject' => $this->subject], 'ar'),
            'body'                => $attributes['body'] ?? null,
            'sender_name'         => Auth::user()?->name ?? config('app.name'),
            'sender_entity'       => $this->sender_entity,
            'from_department_id'  => $this->to_department_id,
            'to_department_id'    => $this->from_department_id,
            'to_user_id'          => $this->creator_id,
            'to_external_email'   => $this->isIncoming() ? null : $this->to_external_email,
            'status'              => $this->isIncoming() ? 'draft' : 'received',
            'received_at'         => $this->isOutgoing() ? now()->toDateString() : null,
            'due_date'            => $attributes['due_date'] ?? null,
            'project_id'          => $this->project_id,
            'meeting_id'          => $this->meeting_id,
            'purchase_request_id' => $this->purchase_request_id,
            'company_id'          => $this->company_id,
            'creator_id'          => Auth::id() ?? $this->creator_id,
        ]);

        $this->notifyReplyCreated($reply);

        return $reply;
    }

    public function onApprovalSubmitted(Approval $approval): void
    {
        if (! $this->isOutgoing()) {
            return;
        }

        $this->updateQuietly(['status' => 'pending_approval']);
        $this->notifyCurrentApprovers();
    }

    public function onApprovalApproved(Approval $approval): void
    {
        if (! $this->isOutgoing()) {
            return;
        }

        $this->updateQuietly(['status' => 'approved']);
        $this->notifyCreatorApproved();
    }

    public function onApprovalRejected(Approval $approval): void
    {
        if (! $this->isOutgoing()) {
            return;
        }

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

        $this->databaseNotification('submitted')
            ->body(__('correspondence::correspondence.notify.submitted.body', [
                'reference' => $this->reference_number,
                'subject'   => $this->subject,
            ]))
            ->sendToDatabase($users);
    }

    public function notifyCreatorApproved(): void
    {
        if (! $this->creator) {
            return;
        }

        $this->databaseNotification('approved')
            ->body(__('correspondence::correspondence.notify.approved.body', [
                'reference' => $this->reference_number,
            ]))
            ->sendToDatabase($this->creator);
    }

    public function notifyCreatorRejected(?string $reason): void
    {
        if (! $this->creator) {
            return;
        }

        $this->databaseNotification('rejected')
            ->body(__('correspondence::correspondence.notify.rejected.body', [
                'reference' => $this->reference_number,
                'reason'    => $reason ?: __('correspondence::correspondence.notify.rejected.no_reason'),
            ]))
            ->sendToDatabase($this->creator);
    }

    public function notifyIncomingReceived(): void
    {
        $userIds = array_filter([
            $this->to_user_id,
            $this->toDepartment?->manager_id,
        ]);

        $users = User::query()->whereIn('id', $userIds)->get();

        if ($users->isEmpty()) {
            return;
        }

        $this->databaseNotification('received')
            ->body(__('correspondence::correspondence.notify.received.body', [
                'sender'  => $this->sender_name,
                'subject' => $this->subject,
            ]))
            ->sendToDatabase($users);
    }

    public function notifyCreatorAndFollowers(string $key): void
    {
        $userIds = collect([$this->creator_id])
            ->merge($this->followers()->pluck('user_id'))
            ->filter()
            ->unique()
            ->all();

        if ($userIds === []) {
            return;
        }

        $users = User::query()->whereIn('id', $userIds)->get();

        $this->databaseNotification($key)
            ->body(__('correspondence::correspondence.notify.'.$key.'.body', [
                'reference' => $this->reference_number,
                'target'    => $this->to_external_email ?: ($this->toUser?->name ?? '-'),
            ]))
            ->sendToDatabase($users);
    }

    public function notifyReplyCreated(self $reply): void
    {
        $userIds = collect([$this->creator_id])
            ->merge($this->followers()->pluck('user_id'))
            ->filter()
            ->unique()
            ->all();

        if ($userIds === []) {
            return;
        }

        $this->databaseNotification('reply')
            ->body(__('correspondence::correspondence.notify.reply.body', [
                'reference' => $reply->reference_number,
                'subject'   => $reply->subject,
            ]))
            ->sendToDatabase(User::query()->whereIn('id', $userIds)->get());
    }

    protected function databaseNotification(string $key): Notification
    {
        return Notification::make()
            ->title(__("correspondence::correspondence.notify.{$key}.title"))
            ->actions([
                Action::make('view')
                    ->label(__('correspondence::correspondence.actions.view'))
                    ->url(CorrespondenceResource::getUrl('view', ['record' => $this])),
            ]);
    }

    protected static function newFactory(): CorrespondenceFactory
    {
        return CorrespondenceFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Correspondence $correspondence): void {
            $correspondence->creator_id ??= Auth::id();
            $correspondence->company_id ??= Auth::user()?->default_company_id;
            $correspondence->sender_name ??= Auth::user()?->name ?? config('app.name');

            if ($correspondence->isIncoming()) {
                $correspondence->status = 'received';
                $correspondence->received_at ??= now()->toDateString();
            }

            $correspondence->reference_number ??= static::nextReferenceNumber($correspondence);
        });

        static::created(function (Correspondence $correspondence): void {
            if ($correspondence->isIncoming()) {
                $correspondence->notifyIncomingReceived();
            }
        });
    }

    protected static function nextReferenceNumber(Correspondence $correspondence): string
    {
        return DB::transaction(function () use ($correspondence): string {
            $dirCode = $correspondence->direction === 'outgoing' ? 'OUT' : 'IN';
            $deptCode = Department::query()->find($correspondence->from_department_id)?->code ?? 'GEN';
            $year = now()->year;
            $sequence = static::query()
                ->whereYear('created_at', $year)
                ->where('direction', $correspondence->direction)
                ->lockForUpdate()
                ->count() + 1;

            return sprintf('%s/%s/%d/%04d', $dirCode, $deptCode, $year, $sequence);
        });
    }

    protected static function correspondenceTranslation(string $key, array $replace = [], ?string $locale = null): string
    {
        $line = trans("correspondence::correspondence.{$key}", $replace, $locale);

        if ($line !== "correspondence::correspondence.{$key}") {
            return $line;
        }

        $lines = trans()->getLoader()->load($locale ?? app()->getLocale(), 'correspondence', 'correspondence');
        $line = data_get($lines, $key, $key);

        foreach ($replace as $search => $value) {
            $line = str_replace(':'.$search, (string) $value, $line);
        }

        return $line;
    }
}
