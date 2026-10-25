<?php

namespace App\Traits;

use Illuminate\Support\Facades\Schema;
use Wezlo\FilamentApproval\Concerns\HasApprovals;
use Wezlo\FilamentApproval\Enums\ApprovalStatus;
use Wezlo\FilamentApproval\Models\ApprovalFlow;

trait HasApprovalWorkflow
{
    use HasApprovals {
        canBeSubmittedForApproval as canBeSubmittedForApprovalIgnoringFlows;
    }

    public function approvalStatusLabel(): string
    {
        $status = $this->approvalStatus();

        if ($status === null) {
            return __('approval.status.not_submitted');
        }

        return match ($status) {
            ApprovalStatus::Pending   => __('approval.status.pending'),
            ApprovalStatus::Approved  => __('approval.status.approved'),
            ApprovalStatus::Rejected  => __('approval.status.rejected'),
            ApprovalStatus::Cancelled => __('approval.status.cancelled'),
        };
    }

    /**
     * Submitting needs an active flow; without one the submit action would fail with a 404.
     */
    public function canBeSubmittedForApproval(?int $userId = null): bool
    {
        return $this->hasActiveApprovalFlow()
            && $this->canBeSubmittedForApprovalIgnoringFlows($userId);
    }

    public function hasActiveApprovalFlow(): bool
    {
        if (! Schema::hasTable('approval_flows')) {
            return false;
        }

        return ApprovalFlow::query()
            ->where('approvable_type', $this->getMorphClass())
            ->where('is_active', true)
            ->exists();
    }

    public function canBePosted(): bool
    {
        return $this->isApproved();
    }

    public function guardApprovalBeforePosting(): void
    {
        if (! $this->hasActiveApprovalFlow()) {
            return;
        }

        if (! $this->canBePosted()) {
            throw new \RuntimeException(__('approval.errors.not_approved'));
        }
    }
}
