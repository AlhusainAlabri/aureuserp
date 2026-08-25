<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Assets\Models\AssetBorrowing;
use Wezlo\FilamentApproval\ApproverResolvers\RoleResolver;
use Wezlo\FilamentApproval\Enums\StepType;
use Wezlo\FilamentApproval\Models\ApprovalFlow;
use Wezlo\FilamentApproval\Models\ApprovalStep;

class AssetBorrowingApprovalFlowSeeder extends Seeder
{
    public function run(): void
    {
        if (! class_exists(AssetBorrowing::class)) {
            return;
        }

        $morphClass = (new AssetBorrowing)->getMorphClass();

        $flow = ApprovalFlow::firstOrCreate(
            ['approvable_type' => $morphClass],
            [
                'name'        => __('assets-extensions::approval.flow_name'),
                'description' => __('assets-extensions::approval.flow_description'),
                'is_active'   => true,
            ]
        );

        $flow->update([
            'name'        => __('assets-extensions::approval.flow_name'),
            'description' => __('assets-extensions::approval.flow_description'),
            'is_active'   => true,
        ]);

        $steps = [
            ['order' => 1, 'name' => __('assets-extensions::approval.steps.manager'), 'role' => 'manager'],
            ['order' => 2, 'name' => __('assets-extensions::approval.steps.assets_manager'), 'role' => 'manager'],
        ];

        foreach ($steps as $step) {
            ApprovalStep::updateOrCreate(
                [
                    'approval_flow_id' => $flow->id,
                    'order'            => $step['order'],
                ],
                [
                    'name'               => $step['name'],
                    'type'               => StepType::Single,
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
