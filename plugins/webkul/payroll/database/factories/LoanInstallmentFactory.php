<?php

namespace Webkul\Payroll\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Payroll\Enums\LoanInstallmentStatus;
use Webkul\Payroll\Models\Loan;
use Webkul\Payroll\Models\LoanInstallment;

/**
 * @extends Factory<LoanInstallment>
 */
class LoanInstallmentFactory extends Factory
{
    protected $model = LoanInstallment::class;

    public function definition(): array
    {
        $year = (int) now()->year;
        $month = (int) now()->month;

        return [
            'loan_id'      => Loan::factory(),
            'payslip_id'   => null,
            'period_year'  => $year,
            'period_month' => $month,
            'amount'       => fake()->randomFloat(3, 50, 500),
            'status'       => LoanInstallmentStatus::Scheduled,
            'deducted_at'  => null,
        ];
    }

    public function deducted(): static
    {
        return $this->state(fn (): array => [
            'status'      => LoanInstallmentStatus::Deducted,
            'deducted_at' => now(),
        ]);
    }
}
