<?php

namespace Webkul\Meetings\Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Meetings\Models\Meeting;
use Wezlo\FilamentApproval\ApproverResolvers\RoleResolver;
use Wezlo\FilamentApproval\Enums\StepType;
use Wezlo\FilamentApproval\Models\ApprovalFlow;

class MeetingApprovalFlowSeeder extends Seeder
{
    public function run(): void
    {
        if (ApprovalFlow::query()->where('approvable_type', (new Meeting)->getMorphClass())->exists()) {
            return;
        }

        $flow = ApprovalFlow::query()->create([
            'name'            => __('meetings::meetings.approvals.default_flow'),
            'approvable_type' => (new Meeting)->getMorphClass(),
            'is_active'       => true,
        ]);

        $flow->steps()->createMany([
            [
                'name'               => __('meetings::meetings.approvals.steps.direct_manager'),
                'order'              => 1,
                'type'               => StepType::Single,
                'approver_resolver'  => RoleResolver::class,
                'approver_config'    => ['role' => 'manager'],
                'required_approvals' => 1,
            ],
            [
                'name'               => __('meetings::meetings.approvals.steps.admin_manager'),
                'order'              => 2,
                'type'               => StepType::Single,
                'approver_resolver'  => RoleResolver::class,
                'approver_config'    => ['role' => 'admin_manager'],
                'required_approvals' => 1,
            ],
        ]);
    }
}
