<?php

namespace App\Traits;

use Wezlo\FilamentApproval\Concerns\HasApprovals;
use Wezlo\FilamentApproval\Enums\ApprovalStatus;

trait HasApprovalWorkflow
{
    use HasApprovals;

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

    public function canBePosted(): bool
    {
        return $this->isApproved();
    }

    protected function guardApprovalBeforePosting(): void
    {
        if (! $this->canBePosted()) {
            throw new \RuntimeException(__('approval.errors.not_approved'));
        }
    }
}
