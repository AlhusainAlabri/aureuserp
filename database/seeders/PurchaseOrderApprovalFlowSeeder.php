<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Purchase\Models\PurchaseOrder;
use Wezlo\FilamentApproval\ApproverResolvers\RoleResolver;
use Wezlo\FilamentApproval\Models\ApprovalFlow;

class PurchaseOrderApprovalFlowSeeder extends Seeder
{
    public function run(): void
    {
        $morphClass = (new PurchaseOrder)->getMorphClass();

        if (ApprovalFlow::where('approvable_type', $morphClass)->exists()) {
            return;
        }

        $flow = ApprovalFlow::create([
            'name'            => 'Purchase Order Approval Flow',
            'description'     => '2-step sequential approval flow for purchase orders.',
            'approvable_type' => $morphClass,
            'is_active'       => true,
        ]);

        $steps = [
            ['order' => 1, 'name' => 'Department Manager Review', 'role' => 'manager'],
            ['order' => 2, 'name' => 'Finance Manager Review',    'role' => 'finance_manager'],
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
