<?php

namespace App\Console\Commands;

use App\Services\Approvals\ApprovalNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Wezlo\FilamentApproval\Enums\StepInstanceStatus;
use Wezlo\FilamentApproval\Models\ApprovalStepInstance;

class NotifyApprovalSlaWarnings extends Command
{
    protected $signature = 'approval:notify-sla-emails';

    protected $description = 'Send email notifications for approval SLA warnings';

    public function handle(ApprovalNotificationService $notifications): int
    {
        if (! Schema::hasTable('approval_step_instances')) {
            return self::SUCCESS;
        }

        $instances = ApprovalStepInstance::query()
            ->where('status', StepInstanceStatus::Waiting)
            ->where('sla_warning_sent', true)
            ->with(['approval.approvable', 'step'])
            ->get();

        $sent = 0;

        foreach ($instances as $instance) {
            $notifications->notifyStepApprovers($instance, 'approvals.sla_warning');
            $sent++;
        }

        $this->info("Processed SLA warning emails for {$sent} approval step(s).");

        return self::SUCCESS;
    }
}
