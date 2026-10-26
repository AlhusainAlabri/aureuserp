<?php

namespace Webkul\Payroll\Services;

use App\Models\Hr\EmployeeContract;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Models\EmployeeComponent;
use Webkul\Payroll\Models\SalaryComponent;

/**
 * Builds the minimum salary assignments of an employee from the active contract: the BASIC salary
 * (the contract wage) and the two PASI components. Payroll reads only assignments, so without them an
 * employee with a contract still gets an empty payslip.
 */
class SalaryStructureAssigner
{
    /**
     * @var list<string>
     */
    public const array COMPONENT_CODES = ['BASIC', 'PASI_EMP', 'PASI_EMR'];

    /**
     * The contract in force today (latest start date), if any.
     */
    public function activeContract(Employee $employee, ?CarbonInterface $on = null): ?EmployeeContract
    {
        if (! Schema::hasTable('employee_contracts')) {
            return null;
        }

        $date = ($on ?? now())->toDateString();

        return EmployeeContract::query()
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $date)
            ->where(function ($query) use ($date): void {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', $date);
            })
            ->orderByDesc('start_date')
            ->first();
    }

    /**
     * Creates the assignments the employee does not have yet.
     *
     * @return list<string> codes of the components that were assigned
     */
    public function assignFromContract(Employee $employee): array
    {
        $contract = $this->activeContract($employee);

        if ($contract === null || (float) $contract->wage <= 0) {
            return [];
        }

        $start = $contract->first_joining_date ?? $contract->start_date;
        $assigned = [];

        foreach (self::COMPONENT_CODES as $code) {
            $component = SalaryComponent::query()->active()->where('code', $code)->first();

            if ($component === null) {
                continue;
            }

            $alreadyAssigned = EmployeeComponent::query()
                ->where('employee_id', $employee->id)
                ->where('component_id', $component->id)
                ->where(function ($query): void {
                    $query->whereNull('end_date')->orWhereDate('end_date', '>=', now()->toDateString());
                })
                ->exists();

            if ($alreadyAssigned) {
                continue;
            }

            EmployeeComponent::query()->create([
                'employee_id'  => $employee->id,
                'component_id' => $component->id,
                'amount'       => $code === 'BASIC' ? $contract->wage : null,
                'start_date'   => $start,
                'creator_id'   => Auth::id(),
            ]);

            $assigned[] = $code;
        }

        return $assigned;
    }
}
