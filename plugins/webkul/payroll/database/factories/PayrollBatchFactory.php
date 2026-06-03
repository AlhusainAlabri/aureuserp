<?php

namespace Webkul\Payroll\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Payroll\Enums\BatchStatus;
use Webkul\Payroll\Models\PayrollBatch;
use Webkul\Support\Models\Company;

/**
 * @extends Factory<PayrollBatch>
 */
class PayrollBatchFactory extends Factory
{
    protected $model = PayrollBatch::class;

    public function definition(): array
    {
        $year = (int) now()->year;
        $month = (int) now()->month;

        return [
            'reference_number'    => sprintf('PAY-%d-%02d', $year, $month),
            'period_year'         => $year,
            'period_month'        => $month,
            'pay_date'            => now()->endOfMonth(),
            'status'              => BatchStatus::Draft,
            'total_gross'         => 0,
            'total_deductions'    => 0,
            'total_net'           => 0,
            'total_employer_cost' => 0,
            'employee_count'      => 0,
            'journal_id'          => null,
            'account_move_id'     => null,
            'notes'               => fake()->optional()->sentence(),
            'company_id'          => Company::query()->value('id'),
            'creator_id'          => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status'      => BatchStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status'  => BatchStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
