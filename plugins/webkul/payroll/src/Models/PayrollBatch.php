<?php

namespace Webkul\Payroll\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Webkul\Account\Models\Journal;
use Webkul\Account\Models\Move;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Payroll\Database\Factories\PayrollBatchFactory;
use Webkul\Payroll\Enums\BatchStatus;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Wezlo\FilamentApproval\Models\Approval;

class PayrollBatch extends Model
{
    use HasApprovalWorkflow, HasChatter, HasFactory, SoftDeletes {
        HasApprovalWorkflow::isApproved as isWorkflowApproved;
    }

    protected $table = 'payroll_batches';

    protected $fillable = [
        'reference_number',
        'period_year',
        'period_month',
        'pay_date',
        'status',
        'total_gross',
        'total_deductions',
        'total_net',
        'total_employer_cost',
        'employee_count',
        'journal_id',
        'account_move_id',
        'notes',
        'approved_at',
        'paid_at',
        'posted_at',
        'company_id',
        'creator_id',
    ];

    protected function casts(): array
    {
        return [
            'status'              => BatchStatus::class,
            'pay_date'            => 'date',
            'approved_at'         => 'datetime',
            'paid_at'             => 'datetime',
            'posted_at'           => 'datetime',
            'total_gross'         => 'decimal:3',
            'total_deductions'    => 'decimal:3',
            'total_net'           => 'decimal:3',
            'total_employer_cost' => 'decimal:3',
        ];
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class, 'batch_id');
    }

    public function journal(): BelongsTo
    {
        $related = class_exists(Journal::class)
            ? Journal::class
            : Model::class;

        return $this->belongsTo($related, 'journal_id');
    }

    public function accountMove(): BelongsTo
    {
        $related = class_exists(Move::class)
            ? Move::class
            : Model::class;

        return $this->belongsTo($related, 'account_move_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', BatchStatus::Draft);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', BatchStatus::Approved);
    }

    public function isDraft(): bool
    {
        return $this->status === BatchStatus::Draft;
    }

    public function isApproved(): bool
    {
        return $this->status === BatchStatus::Approved;
    }

    public function isPaid(): bool
    {
        return in_array($this->status, [BatchStatus::Paid, BatchStatus::Posted], true);
    }

    public function isPosted(): bool
    {
        return $this->status === BatchStatus::Posted;
    }

    public function isFullyApproved(): bool
    {
        return $this->isWorkflowApproved();
    }

    public function canBePaid(): bool
    {
        return $this->isFullyApproved() && ! $this->isPaid();
    }

    public function canBePosted(): bool
    {
        return $this->isPaid() && ! $this->isPosted();
    }

    public function recalculateTotals(): void
    {
        $this->update([
            'total_gross'         => $this->payslips()->sum('gross_amount'),
            'total_deductions'    => $this->payslips()->sum('deductions_amount'),
            'total_net'           => $this->payslips()->sum('net_amount'),
            'total_employer_cost' => $this->payslips()->sum('employer_cost'),
            'employee_count'      => $this->payslips()->count(),
        ]);
    }

    public function generateReferenceNumber(): string
    {
        return sprintf(
            'PAY-%d-%02d',
            $this->period_year,
            $this->period_month,
        );
    }

    public function onApprovalSubmitted(Approval $approval): void
    {
        $this->updateQuietly(['status' => BatchStatus::PendingApproval]);
    }

    public function onApprovalApproved(Approval $approval): void
    {
        $this->updateQuietly([
            'status'      => BatchStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    public function onApprovalRejected(Approval $approval): void
    {
        $this->updateQuietly(['status' => BatchStatus::Draft]);
    }

    public function markAsPaid(): void
    {
        if (! $this->canBePaid()) {
            throw new RuntimeException(__('payroll::exceptions.batch_cannot_be_paid'));
        }

        $this->update([
            'status'  => BatchStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function markAsPosted(): void
    {
        if (! $this->canBePosted()) {
            throw new RuntimeException(__('payroll::exceptions.batch_cannot_be_posted'));
        }

        $this->update([
            'status'    => BatchStatus::Posted,
            'posted_at' => now(),
        ]);
    }

    protected static function newFactory(): PayrollBatchFactory
    {
        return PayrollBatchFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $batch): void {
            $batch->creator_id ??= Auth::id();
            $batch->company_id ??= Auth::user()?->default_company_id;
            $batch->reference_number ??= $batch->generateReferenceNumber();
        });
    }
}
