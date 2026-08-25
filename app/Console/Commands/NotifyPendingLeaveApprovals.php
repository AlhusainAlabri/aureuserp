<?php

namespace App\Console\Commands;

use App\Services\Hr\HrNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Webkul\Security\Models\User;
use Webkul\TimeOff\Models\Leave;

class NotifyPendingLeaveApprovals extends Command
{
    protected $signature = 'hr:notify-pending-leave-approvals';

    protected $description = 'Remind leave managers about pending leave approval requests';

    public function handle(HrNotificationService $notifications): int
    {
        if (! class_exists(Leave::class)) {
            return self::SUCCESS;
        }

        $today = Carbon::today()->toDateString();

        $pendingLeaves = Leave::query()
            ->whereIn('state', ['confirm', 'validate_one'])
            ->where('date_from', '>=', Carbon::today())
            ->with(['employee', 'holidayStatus'])
            ->get()
            ->groupBy(fn (Leave $leave) => $leave->employee?->leave_manager_id ?? 'unassigned');

        $notified = 0;

        foreach ($pendingLeaves as $managerId => $leaves) {
            if ($managerId === 'unassigned' || ! $managerId) {
                continue;
            }

            $manager = User::query()->find($managerId);

            if (! $manager) {
                continue;
            }

            $title = __('hr-extensions::leave.notifications.pending_approvals_title');
            $body = __('hr-extensions::leave.notifications.pending_approvals_body', [
                'count' => $leaves->count(),
            ]);

            $sent = $notifications->dispatchAlert(
                recipient: $manager,
                notificationKey: 'hr.pending_leave_approvals',
                title: $title,
                body: $body,
                dedupKey: "hr.pending_leave_approvals:manager:{$manager->id}:{$today}",
            );

            if ($sent) {
                $notified++;
            }
        }

        $this->info("Notified {$notified} leave managers.");

        return self::SUCCESS;
    }
}
