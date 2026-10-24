<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Invoice\Models\Invoice;
use Wezlo\FilamentApproval\ApproverResolvers\RoleResolver;
use Wezlo\FilamentApproval\Models\ApprovalFlow;
use Wezlo\FilamentApproval\Models\ApprovalStep;

class InvoiceApprovalFlowSeeder extends Seeder
{
    public function run(): void
    {
        $models = array_filter([
            class_exists(Invoice::class) ? Invoice::class : null,
            class_exists(\Webkul\Accounting\Models\Invoice::class) ? \Webkul\Accounting\Models\Invoice::class : null,
        ]);

        foreach ($models as $modelClass) {
            $this->seedFlowForModel($modelClass);
        }
    }

    protected function seedFlowForModel(string $modelClass): void
    {
        $morphClass = (new $modelClass)->getMorphClass();

        $flow = ApprovalFlow::firstOrCreate(
            ['approvable_type' => $morphClass],
            [
                'name'        => __('invoices-extensions::approval.flow_name'),
                'description' => __('invoices-extensions::approval.flow_description'),
                'is_active'   => false,
            ]
        );

        $flow->update([
            'name'        => __('invoices-extensions::approval.flow_name'),
            'description' => __('invoices-extensions::approval.flow_description'),
        ]);

        $steps = [
            ['order' => 1, 'name' => __('invoices-extensions::approval.steps.manager'), 'role' => 'manager'],
            ['order' => 2, 'name' => __('invoices-extensions::approval.steps.finance_manager'), 'role' => 'finance_manager'],
            ['order' => 3, 'name' => __('invoices-extensions::approval.steps.general_manager'), 'role' => 'general_manager'],
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
