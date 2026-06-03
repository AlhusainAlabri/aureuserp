<?php

namespace Webkul\Payroll\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Enums\PaymentMethod;
use Webkul\Payroll\Enums\PayslipStatus;
use Webkul\Payroll\Models\PayrollBatch;
use Webkul\Payroll\Models\Payslip;

/**
 * @extends Factory<Payslip>
 */
class PayslipFactory extends Factory
{
    protected $model = Payslip::class;

    public function definition(): array
    {
        $year = (int) now()->year;
        $month = (int) now()->month;
        $basic = fake()->randomFloat(3, 300, 2500);
        $gross = round($basic * 1.35, 3);
        $deductions = round($basic * 0.07, 3);

        return [
            'reference_number'    => sprintf('PSL-%d-%02d-%04d', $year, $month, fake()->numberBetween(1, 9999)),
            'batch_id'            => PayrollBatch::factory(),
            'employee_id'         => Employee::query()->value('id'),
            'contract_id'         => null,
            'period_year'         => $year,
            'period_month'        => $month,
            'working_days'        => 22,
            'worked_days'         => 22,
            'unpaid_leave_days'   => 0,
            'basic_salary'        => $basic,
            'gross_amount'        => $gross,
            'deductions_amount'   => $deductions,
            'net_amount'          => round($gross - $deductions, 3),
            'employer_cost'       => round($basic * 0.105, 3),
            'payment_method'      => PaymentMethod::BankTransfer,
            'bank_account_number' => fake()->numerify('################'),
            'bank_name'           => fake()->company(),
            'cheque_number'       => null,
            'status'              => PayslipStatus::Draft,
            'notes'               => null,
            'creator_id'          => null,
        ];
    }

    public function validated(): static
    {
        return $this->state(fn (): array => ['status' => PayslipStatus::Validated]);
    }

    public function paid(): static
    {
        return $this->state(fn (): array => ['status' => PayslipStatus::Paid]);
    }
}
