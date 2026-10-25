<?php

namespace Tests\Helpers;

use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Enums\CalculationType;
use Webkul\Payroll\Enums\PasiCategory;
use Webkul\Payroll\Enums\SalaryComponentType;
use Webkul\Payroll\Models\EmployeeComponent;
use Webkul\Payroll\Models\PasiRule;
use Webkul\Payroll\Models\SalaryComponent;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Country;

class PasiFixtures
{
    public static function country(string $code): Country
    {
        return Country::query()->firstOrCreate(
            ['code' => $code],
            ['name' => $code, 'phone_code' => 0, 'state_required' => false, 'zip_required' => false],
        );
    }

    public static function company(?string $countryCode = 'OM'): Company
    {
        return Company::factory()->create([
            'currency_id' => null,
            'country_id'  => $countryCode === null ? null : static::country($countryCode)->id,
        ]);
    }

    /**
     * @return array{basic: SalaryComponent, employee: SalaryComponent, employer: SalaryComponent}
     */
    public static function components(): array
    {
        $basic = SalaryComponent::query()->updateOrCreate(['code' => 'BASIC'], [
            'name'               => 'Basic Salary',
            'name_ar'            => 'الراتب الأساسي',
            'type'               => SalaryComponentType::Earning,
            'calculation_type'   => CalculationType::Fixed,
            'default_amount'     => 1000,
            'default_percent'    => null,
            'is_pasi_insurable'  => true,
            'is_active'          => true,
            'sort_order'         => 10,
        ]);

        $employee = SalaryComponent::query()->updateOrCreate(['code' => 'PASI_EMP'], [
            'name'               => 'PASI Employee Share',
            'name_ar'            => 'حصة الموظف',
            'type'               => SalaryComponentType::Deduction,
            'calculation_type'   => CalculationType::PasiEmployee,
            'default_amount'     => null,
            'default_percent'    => null,
            'is_pasi_insurable'  => false,
            'is_active'          => true,
            'sort_order'         => 40,
        ]);

        $employer = SalaryComponent::query()->updateOrCreate(['code' => 'PASI_EMR'], [
            'name'               => 'PASI Employer Share',
            'name_ar'            => 'حصة صاحب العمل',
            'type'               => SalaryComponentType::EmployerCost,
            'calculation_type'   => CalculationType::PasiEmployer,
            'default_amount'     => null,
            'default_percent'    => null,
            'is_pasi_insurable'  => false,
            'is_active'          => true,
            'sort_order'         => 50,
        ]);

        return ['basic' => $basic, 'employee' => $employee, 'employer' => $employer];
    }

    /**
     * Creates an employee with a BASIC assignment and, unless disabled, both PASI components assigned.
     */
    public static function employee(
        Company $company,
        ?string $nationalityCode,
        float $basic = 1000,
        bool $withPasi = true,
    ): Employee {
        $components = static::components();

        $employee = Employee::query()->create([
            'company_id' => $company->id,
            'country_id' => $nationalityCode === null ? null : static::country($nationalityCode)->id,
            'name'       => 'PASI Test Employee '.uniqid(),
            'work_email' => 'pasi-'.uniqid().'@test.example',
            'is_active'  => true,
        ]);

        $assigned = $withPasi
            ? [$components['basic'], $components['employee'], $components['employer']]
            : [$components['basic']];

        foreach ($assigned as $component) {
            EmployeeComponent::query()->create([
                'employee_id'  => $employee->id,
                'component_id' => $component->id,
                'amount'       => $component->code === 'BASIC' ? $basic : null,
                'start_date'   => '2000-01-01',
            ]);
        }

        return $employee;
    }

    public static function rule(
        PasiCategory $category,
        float $employeeRate,
        float $employerRate,
        ?float $cap,
        string $from,
        ?string $to = null,
    ): PasiRule {
        return PasiRule::query()->create([
            'category'       => $category,
            'employee_rate'  => $employeeRate,
            'employer_rate'  => $employerRate,
            'salary_cap'     => $cap,
            'effective_from' => $from,
            'effective_to'   => $to,
        ]);
    }

    /**
     * Removes every rule so a test controls the full rule set (rolled back with the test transaction).
     */
    public static function clearRules(): void
    {
        PasiRule::query()->delete();
    }
}
