<?php

namespace Webkul\Payroll\Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Payroll\Models\EndOfServiceSettlement;
use Wezlo\FilamentApproval\ApproverResolvers\RoleResolver;
use Wezlo\FilamentApproval\Enums\StepType;
use Wezlo\FilamentApproval\Models\ApprovalFlow;

class EndOfServiceApprovalFlowSeeder extends Seeder
{
    public function run(): void
    {
        $morphClass = (new EndOfServiceSettlement)->getMorphClass();

        if (ApprovalFlow::query()->where('approvable_type', $morphClass)->exists()) {
            return;
        }

        $flow = ApprovalFlow::query()->create([
            'name'            => __('payroll::seeders.settlement_approval.flow_name'),
            'approvable_type' => $morphClass,
            'is_active'       => false,
        ]);

        foreach (['hr_manager', 'finance_manager', 'general_manager'] as $index => $role) {
            $flow->steps()->create([
                'name'               => __('payroll::seeders.settlement_approval.steps.'.$role),
                'order'              => $index + 1,
                'type'               => StepType::Single,
                'approver_resolver'  => RoleResolver::class,
                'approver_config'    => ['role' => $role],
                'required_approvals' => 1,
            ]);
        }
    }
}
