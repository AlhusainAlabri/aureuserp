<?php

namespace Webkul\Correspondence\Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Correspondence\Models\Correspondence;
use Wezlo\FilamentApproval\ApproverResolvers\RoleResolver;
use Wezlo\FilamentApproval\Enums\StepType;
use Wezlo\FilamentApproval\Models\ApprovalFlow;

class CorrespondenceApprovalFlowSeeder extends Seeder
{
    public function run(): void
    {
        if (ApprovalFlow::query()->where('approvable_type', (new Correspondence)->getMorphClass())->exists()) {
            return;
        }

        $flow = ApprovalFlow::query()->create([
            'name'            => __('correspondence::correspondence.approvals.default_flow'),
            'approvable_type' => (new Correspondence)->getMorphClass(),
            'is_active'       => true,
        ]);

        $flow->steps()->createMany([
            [
                'name'               => __('correspondence::correspondence.approvals.steps.department_manager'),
                'order'              => 1,
                'type'               => StepType::Single,
                'approver_resolver'  => RoleResolver::class,
                'approver_config'    => ['role' => 'manager'],
                'required_approvals' => 1,
            ],
            [
                'name'               => __('correspondence::correspondence.approvals.steps.admin_manager'),
                'order'              => 2,
                'type'               => StepType::Single,
                'approver_resolver'  => RoleResolver::class,
                'approver_config'    => ['role' => 'admin_manager'],
                'required_approvals' => 1,
            ],
        ]);
    }
}
