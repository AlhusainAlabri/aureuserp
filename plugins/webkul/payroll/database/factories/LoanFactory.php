<?php

namespace Webkul\Payroll\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Enums\LoanStatus;
use Webkul\Payroll\Enums\LoanType;
use Webkul\Payroll\Models\Loan;
use Webkul\Support\Models\Company;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        $year = (int) now()->year;
        $month = (int) now()->month;
        $total = fake()->randomFloat(3, 500, 5000);
        $installments = fake()->numberBetween(3, 24);

        return [
            'reference_number'   => sprintf('LOAN-%d-%04d', $year, fake()->numberBetween(1, 9999)),
            'employee_id'        => Employee::query()->value('id'),
            'loan_type'          => fake()->randomElement(LoanType::cases()),
            'total_amount'       => $total,
            'installment_count'  => $installments,
            'installment_amount' => round($total / $installments, 3),
            'start_period_year'  => $year,
            'start_period_month' => $month,
            'end_period_year'    => $year,
            'end_period_month'   => $month,
            'reason'             => fake()->sentence(),
            'status'             => LoanStatus::Draft,
            'amount_repaid'      => 0,
            'amount_remaining'   => $total,
            'company_id'         => Company::query()->value('id'),
            'creator_id'         => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status'      => LoanStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['status' => LoanStatus::Active]);
    }
}
