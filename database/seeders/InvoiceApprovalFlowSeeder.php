<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Invoice\Models\Invoice;
use Wezlo\FilamentApproval\ApproverResolvers\RoleResolver;
use Wezlo\FilamentApproval\Models\ApprovalFlow;

class InvoiceApprovalFlowSeeder extends Seeder
{
    public function run(): void
    {
        $morphClass = (new Invoice)->getMorphClass();

        if (ApprovalFlow::where('approvable_type', $morphClass)->exists()) {
            return;
        }

        $flow = ApprovalFlow::create([
            'name'            => 'Invoice Approval Flow',
            'description'     => '3-step sequential approval flow for customer invoices.',
            'approvable_type' => $morphClass,
            'is_active'       => true,
        ]);

        $steps = [
            ['order' => 1, 'name' => 'Accountant Review',    'role' => 'accountant'],
            ['order' => 2, 'name' => 'Finance Manager Review', 'role' => 'finance_manager'],
            ['order' => 3, 'name' => 'CFO Approval',          'role' => 'cfo'],
        ];

        foreach ($steps as $step) {
            $flow->steps()->create([
                'name'               => $step['name'],
                'order'              => $step['order'],
                'type'               => 'single',
                'approver_resolver'  => RoleResolver::class,
                'approver_config'    => ['role' => $step['role']],
                'required_approvals' => 1,
            ]);
        }
    }
}
