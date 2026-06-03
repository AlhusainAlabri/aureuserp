<?php

namespace Webkul\Payroll\Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Payroll\Models\Loan;
use Wezlo\FilamentApproval\ApproverResolvers\RoleResolver;
use Wezlo\FilamentApproval\Enums\StepType;
use Wezlo\FilamentApproval\Models\ApprovalFlow;

class LoanApprovalFlowSeeder extends Seeder
{
    public function run(): void
    {
        if (! class_exists(Loan::class)) {
            return;
        }

        if (ApprovalFlow::query()->where('approvable_type', (new Loan)->getMorphClass())->exists()) {
            return;
        }

        $flow = ApprovalFlow::query()->create([
            'name'            => __('payroll::seeders.loan_approval.flow_name'),
            'approvable_type' => (new Loan)->getMorphClass(),
            'is_active'       => true,
        ]);

        $flow->steps()->createMany([
            [
                'name'               => __('payroll::seeders.loan_approval.steps.manager'),
                'order'              => 1,
                'type'               => StepType::Single,
                'approver_resolver'  => RoleResolver::class,
                'approver_config'    => ['role' => 'manager'],
                'required_approvals' => 1,
            ],
            [
                'name'               => __('payroll::seeders.loan_approval.steps.hr_manager'),
                'order'              => 2,
                'type'               => StepType::Single,
                'approver_resolver'  => RoleResolver::class,
                'approver_config'    => ['role' => 'hr_manager'],
                'required_approvals' => 1,
            ],
            [
                'name'               => __('payroll::seeders.loan_approval.steps.finance_manager'),
                'order'              => 3,
                'type'               => StepType::Single,
                'approver_resolver'  => RoleResolver::class,
                'approver_config'    => ['role' => 'finance_manager'],
                'required_approvals' => 1,
            ],
        ]);
    }
}
