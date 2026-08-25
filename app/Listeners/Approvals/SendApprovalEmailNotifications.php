<?php

namespace App\Listeners\Approvals;

use App\Services\Approvals\ApprovalNotificationService;
use Illuminate\Support\Facades\DB;
use Wezlo\FilamentApproval\Events\ApprovalCompleted;
use Wezlo\FilamentApproval\Events\ApprovalEscalated;
use Wezlo\FilamentApproval\Events\ApprovalRejected;
use Wezlo\FilamentApproval\Events\ApprovalStepCompleted;
use Wezlo\FilamentApproval\Events\ApprovalSubmitted;

class SendApprovalEmailNotifications
{
    public function __construct(
        private readonly ApprovalNotificationService $notifications,
    ) {}

    public function handleSubmitted(ApprovalSubmitted $event): void
    {
        $step = $event->approval->fresh(['stepInstances.step'])->currentStepInstance();

        if ($step) {
            $this->notifications->notifyStepApprovers($step, 'approvals.requested');
        }
    }

    public function handleStepCompleted(ApprovalStepCompleted $event): void
    {
        DB::afterCommit(function () use ($event): void {
            $step = $event->stepInstance->approval
                ?->fresh(['stepInstances.step'])
                ?->currentStepInstance();

            if ($step && $step->id !== $event->stepInstance->id) {
                $this->notifications->notifyStepApprovers($step, 'approvals.requested');
            }
        });
    }

    public function handleRejected(ApprovalRejected $event): void
    {
        $this->notifications->notifySubmitter($event->approval, 'approvals.rejected');
    }

    public function handleCompleted(ApprovalCompleted $event): void
    {
        $this->notifications->notifySubmitter($event->approval, 'approvals.completed');
    }

    public function handleEscalated(ApprovalEscalated $event): void
    {
        $this->notifications->notifyStepApprovers($event->stepInstance, 'approvals.escalated');
    }
}
