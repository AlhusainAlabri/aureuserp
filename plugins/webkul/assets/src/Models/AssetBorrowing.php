<?php

namespace Webkul\Assets\Models;

use App\Models\Assets\AssetBorrowingEvent;
use App\Services\Assets\AssetBorrowingEventService;
use App\Services\Assets\AssetBorrowingNotificationService;
use App\Services\Assets\AssetSignatureStorageService;
use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Webkul\Assets\Enums\AssetStatus;
use Webkul\Assets\Enums\BorrowingStatus;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;
use Wezlo\FilamentApproval\Models\Approval;

class AssetBorrowing extends Model
{
    use HasApprovalWorkflow;

    protected $fillable = [
        'asset_id',
        'employee_id',
        'borrowed_at',
        'due_at',
        'returned_at',
        'status',
        'notes',
        'borrowed_by',
        'returned_by',
        'requested_by',
        'approved_by',
        'rejected_by',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'borrow_signature_path',
        'return_signature_path',
        'borrow_signed_at',
        'return_signed_at',
        'borrow_signed_by',
        'return_signed_by',
        'due_reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'borrowed_at'          => 'datetime',
            'due_at'               => 'datetime',
            'returned_at'          => 'datetime',
            'approved_at'          => 'datetime',
            'rejected_at'          => 'datetime',
            'borrow_signed_at'     => 'datetime',
            'return_signed_at'     => 'datetime',
            'due_reminder_sent_at' => 'datetime',
            'status'               => BorrowingStatus::class,
        ];
    }

    public function getModelTitle(): string
    {
        return __('assets-extensions::models.borrowing');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function borrowedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'borrowed_by');
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AssetBorrowingEvent::class, 'asset_borrowing_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', BorrowingStatus::Active);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', BorrowingStatus::Pending);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where('status', BorrowingStatus::Overdue)
                ->orWhere(function (Builder $query): void {
                    $query->where('status', BorrowingStatus::Active)
                        ->where('due_at', '<', now());
                });
        });
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, [BorrowingStatus::Active, BorrowingStatus::Overdue], true)
            && $this->due_at?->isPast();
    }

    public function isPending(): bool
    {
        return in_array($this->status, [BorrowingStatus::Pending, BorrowingStatus::PendingApproval], true);
    }

    public static function submitRequest(Asset $asset, Employee $employee, \DateTimeInterface $dueAt, ?string $notes = null): self
    {
        $borrowing = static::query()->create([
            'asset_id'     => $asset->id,
            'employee_id'  => $employee->id,
            'due_at'       => $dueAt,
            'notes'        => $notes,
            'status'       => BorrowingStatus::Pending,
            'requested_by' => Auth::id(),
        ]);

        app(AssetBorrowingEventService::class)->log($borrowing, 'submitted', [
            'due_at' => $dueAt->format('Y-m-d H:i:s'),
        ]);

        app(AssetBorrowingNotificationService::class)->notifySubmitted($borrowing);

        return $borrowing;
    }

    public static function createDirectCheckout(Asset $asset, int $employeeId, \DateTimeInterface $dueAt, ?string $notes = null): self
    {
        $borrowing = static::query()->create([
            'asset_id'    => $asset->id,
            'employee_id' => $employeeId,
            'due_at'      => $dueAt,
            'notes'       => $notes,
            'status'      => BorrowingStatus::Active,
            'borrowed_at' => now(),
            'borrowed_by' => Auth::id(),
        ]);

        $borrowing->activateBorrowing();

        app(AssetBorrowingEventService::class)->log($borrowing, 'borrowed', [
            'direct_checkout' => true,
        ]);

        return $borrowing;
    }

    public function approve(?string $borrowSignature = null): void
    {
        if ($this->status !== BorrowingStatus::Pending) {
            throw new \RuntimeException(__('assets-extensions::requests.errors.not_pending'));
        }

        if ($borrowSignature !== null && str_starts_with($borrowSignature, 'data:')) {
            $this->storeBorrowSignature($borrowSignature);
        }

        $this->update([
            'status'      => BorrowingStatus::Active,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'borrowed_at' => now(),
            'borrowed_by' => Auth::id(),
        ]);

        $this->activateBorrowing();

        app(AssetBorrowingEventService::class)->log($this->fresh(), 'approved');
        app(AssetBorrowingNotificationService::class)->notifyApproved($this->fresh());
    }

    public function reject(?string $reason = null): void
    {
        if ($this->status !== BorrowingStatus::Pending) {
            throw new \RuntimeException(__('assets-extensions::requests.errors.not_pending'));
        }

        $this->update([
            'status'            => BorrowingStatus::Rejected,
            'rejected_by'       => Auth::id(),
            'rejected_at'       => now(),
            'rejection_reason'  => $reason,
        ]);

        app(AssetBorrowingEventService::class)->log($this->fresh(), 'rejected', [
            'reason' => $reason,
        ]);

        app(AssetBorrowingNotificationService::class)->notifyRejected($this->fresh());
    }

    public function activateBorrowing(): void
    {
        $this->asset?->update(['status' => AssetStatus::Borrowed]);
    }

    public function storeBorrowSignature(string $dataUrl): void
    {
        $path = app(AssetSignatureStorageService::class)->storeBorrowSignature($this->id, $dataUrl);

        $this->update([
            'borrow_signature_path' => $path,
            'borrow_signed_at'      => now(),
            'borrow_signed_by'      => Auth::id(),
        ]);

        app(AssetBorrowingEventService::class)->log($this->fresh(), 'signature_captured', [
            'type' => 'borrow',
            'path' => $path,
        ]);
    }

    public function markReturned(?string $returnSignature = null): void
    {
        if ($returnSignature !== null && str_starts_with($returnSignature, 'data:')) {
            $path = app(AssetSignatureStorageService::class)->storeReturnSignature($this->id, $returnSignature);

            $this->update([
                'return_signature_path' => $path,
                'return_signed_at'      => now(),
                'return_signed_by'      => Auth::id(),
            ]);

            app(AssetBorrowingEventService::class)->log($this->fresh(), 'signature_captured', [
                'type' => 'return',
                'path' => $path,
            ]);
        }

        $this->update([
            'status'      => BorrowingStatus::Returned,
            'returned_at' => now(),
            'returned_by' => Auth::id(),
        ]);

        $this->asset?->update(['status' => AssetStatus::Available]);

        app(AssetBorrowingEventService::class)->log($this->fresh(), 'returned');
        app(AssetBorrowingNotificationService::class)->notifyReturned($this->fresh());
    }

    public function markOverdue(): void
    {
        if ($this->status !== BorrowingStatus::Active || ! $this->isOverdue()) {
            return;
        }

        $this->update(['status' => BorrowingStatus::Overdue]);

        app(AssetBorrowingEventService::class)->log($this->fresh(), 'overdue');
    }

    public function onApprovalSubmitted(Approval $approval): void
    {
        $this->updateQuietly(['status' => BorrowingStatus::PendingApproval->value]);

        app(AssetBorrowingEventService::class)->log($this->fresh(), 'approval_submitted');
        app(AssetBorrowingNotificationService::class)->notifySubmitted($this->fresh());
    }

    public function onApprovalApproved(Approval $approval): void
    {
        if ($this->status === BorrowingStatus::PendingApproval) {
            $this->updateQuietly([
                'status'      => BorrowingStatus::Active->value,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'borrowed_at' => now(),
                'borrowed_by' => Auth::id(),
            ]);

            $this->activateBorrowing();
        }

        app(AssetBorrowingEventService::class)->log($this->fresh(), 'approval_approved');
        app(AssetBorrowingNotificationService::class)->notifyApproved($this->fresh());
    }

    public function onApprovalRejected(Approval $approval): void
    {
        $comment = $approval->actions()->latest()->value('comment');

        $this->updateQuietly([
            'status'           => BorrowingStatus::Rejected->value,
            'rejected_by'      => Auth::id(),
            'rejected_at'      => now(),
            'rejection_reason' => $comment,
        ]);

        app(AssetBorrowingEventService::class)->log($this->fresh(), 'approval_rejected', [
            'comment' => $comment,
        ]);

        app(AssetBorrowingNotificationService::class)->notifyRejected($this->fresh());
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (AssetBorrowing $borrowing): void {
            if ($borrowing->status === null) {
                $borrowing->status = BorrowingStatus::Active;
            }

            if (in_array($borrowing->status, [BorrowingStatus::Active, BorrowingStatus::Overdue], true)) {
                $borrowing->borrowed_at ??= now();
                $borrowing->borrowed_by ??= Auth::id();
            }
        });

        static::created(function (AssetBorrowing $borrowing): void {
            if (in_array($borrowing->status, [BorrowingStatus::Active, BorrowingStatus::Overdue], true)) {
                $borrowing->activateBorrowing();
            }
        });
    }
}
