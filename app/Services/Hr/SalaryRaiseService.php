<?php

namespace App\Services\Hr;

use App\Models\Hr\EmployeeSalaryRaise;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Payroll\Models\EmployeeComponent;
use Webkul\Payroll\Models\SalaryComponent;

class SalaryRaiseService
{
    public function applyRaise(EmployeeSalaryRaise $raise): void
    {
        if (Schema::hasTable('employee_contracts') && $raise->contract_id) {
            DB::table('employee_contracts')
                ->where('id', $raise->contract_id)
                ->update(['wage' => $raise->new_amount]);
        }

        if (! Schema::hasTable('payroll_employee_components') || ! class_exists(SalaryComponent::class)) {
            return;
        }

        $basicComponent = SalaryComponent::query()
            ->where('code', 'BASIC')
            ->first();

        if (! $basicComponent) {
            return;
        }

        EmployeeComponent::query()->updateOrCreate(
            [
                'employee_id'  => $raise->employee_id,
                'component_id' => $basicComponent->id,
            ],
            [
                'amount'     => $raise->new_amount,
                'start_date' => $raise->effective_date,
            ],
        );
    }

    public function resolveCurrentBasicAmount(int $employeeId): float
    {
        if (Schema::hasTable('payroll_employee_components') && class_exists(EmployeeComponent::class)) {
            $basicComponent = SalaryComponent::query()->where('code', 'BASIC')->first();

            if ($basicComponent) {
                $assignment = EmployeeComponent::query()
                    ->where('employee_id', $employeeId)
                    ->where('component_id', $basicComponent->id)
                    ->latest('start_date')
                    ->first();

                if ($assignment) {
                    return (float) $assignment->amount;
                }
            }
        }

        if (Schema::hasTable('employee_contracts')) {
            $wage = DB::table('employee_contracts')
                ->where('employee_id', $employeeId)
                ->whereNull('deleted_at')
                ->orderByDesc('start_date')
                ->value('wage');

            if ($wage !== null) {
                return (float) $wage;
            }
        }

        return 0.0;
    }
}
