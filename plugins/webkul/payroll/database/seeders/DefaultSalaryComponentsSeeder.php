<?php

namespace Webkul\Payroll\Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Payroll\Enums\CalculationType;
use Webkul\Payroll\Enums\SalaryComponentType;
use Webkul\Payroll\Models\SalaryComponent;

class DefaultSalaryComponentsSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            [
                'code'              => 'BASIC',
                'name'              => 'Basic Salary',
                'name_ar'           => 'الراتب الأساسي',
                'type'              => SalaryComponentType::Earning,
                'calculation_type'  => CalculationType::Fixed,
                'default_amount'    => null,
                'default_percent'   => null,
                'sort_order'        => 10,
            ],
            [
                'code'              => 'HOUSING',
                'name'              => 'Housing Allowance',
                'name_ar'           => 'بدل سكن',
                'type'              => SalaryComponentType::Earning,
                'calculation_type'  => CalculationType::PercentOfBasic,
                'default_amount'    => null,
                'default_percent'   => 25.00,
                'sort_order'        => 20,
            ],
            [
                'code'              => 'TRANSPORT',
                'name'              => 'Transportation',
                'name_ar'           => 'بدل مواصلات',
                'type'              => SalaryComponentType::Earning,
                'calculation_type'  => CalculationType::Fixed,
                'default_amount'    => 50.000,
                'default_percent'   => null,
                'sort_order'        => 30,
            ],
            [
                'code'              => 'PASI_EMP',
                'name'              => 'PASI Employee Share',
                'name_ar'           => 'حصة الموظف',
                'type'              => SalaryComponentType::Deduction,
                'calculation_type'  => CalculationType::PercentOfBasic,
                'default_amount'    => null,
                'default_percent'   => 7.00,
                'sort_order'        => 40,
            ],
            [
                'code'              => 'PASI_EMR',
                'name'              => 'PASI Employer Share',
                'name_ar'           => 'حصة صاحب العمل',
                'type'              => SalaryComponentType::EmployerCost,
                'calculation_type'  => CalculationType::PercentOfBasic,
                'default_amount'    => null,
                'default_percent'   => 11.50,
                'sort_order'        => 50,
            ],
        ];

        foreach ($components as $componentData) {
            if (SalaryComponent::query()->where('code', $componentData['code'])->exists()) {
                continue;
            }

            SalaryComponent::query()->create([
                ...$componentData,
                'type'             => $componentData['type']->value,
                'calculation_type' => $componentData['calculation_type']->value,
                'is_taxable'       => false,
                'is_active'        => true,
            ]);
        }
    }
}
