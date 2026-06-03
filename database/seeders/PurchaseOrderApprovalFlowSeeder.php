<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Purchase\Models\PurchaseOrder;
use Wezlo\FilamentApproval\ApproverResolvers\RoleResolver;
use Wezlo\FilamentApproval\Models\ApprovalFlow;
use Wezlo\FilamentApproval\Models\ApprovalStep;

class PurchaseOrderApprovalFlowSeeder extends Seeder
{
    public function run(): void
    {
        $morphClass = (new PurchaseOrder)->getMorphClass();

        $flow = ApprovalFlow::firstOrCreate(
            ['approvable_type' => $morphClass],
            [
                'name'        => 'Purchase Order Approval Flow',
                'description' => '3-step sequential approval flow for purchase orders.',
                'is_active'   => true,
            ]
        );

        $flow->update([
            'name'        => 'Purchase Order Approval Flow',
            'description' => '3-step sequential approval flow for purchase orders.',
            'is_active'   => true,
        ]);

        $steps = [
            ['order' => 1, 'name' => 'Department Manager Review', 'role' => 'manager'],
            ['order' => 2, 'name' => 'Finance Manager Review', 'role' => 'finance_manager'],
            ['order' => 3, 'name' => 'General Manager Review', 'role' => 'general_manager'],
        ];

        foreach ($steps as $step) {
            ApprovalStep::updateOrCreate(
                [
                    'approval_flow_id' => $flow->id,
                    'order'            => $step['order'],
                ],
                [
                    'name'               => $step['name'],
                    'type'               => 'single',
                    'approver_resolver'  => RoleResolver::class,
                    'approver_config'    => ['role' => $step['role']],
                    'required_approvals' => 1,
                ]
            );
        }

        $flow->steps()
            ->where('order', '>', count($steps))
            ->delete();
    }
}
