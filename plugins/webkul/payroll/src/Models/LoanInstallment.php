<?php

namespace Webkul\Payroll\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Payroll\Database\Factories\LoanInstallmentFactory;
use Webkul\Payroll\Enums\LoanInstallmentStatus;

class LoanInstallment extends Model
{
    use HasFactory;

    protected $table = 'payroll_loan_installments';

    protected $fillable = [
        'loan_id',
        'payslip_id',
        'period_year',
        'period_month',
        'amount',
        'status',
        'deducted_at',
    ];

    protected function casts(): array
    {
        return [
            'status'      => LoanInstallmentStatus::class,
            'amount'      => 'decimal:3',
            'deducted_at' => 'datetime',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class, 'payslip_id');
    }

    protected static function newFactory(): LoanInstallmentFactory
    {
        return LoanInstallmentFactory::new();
    }

    public function markDeducted(Payslip $payslip): void
    {
        $this->update([
            'payslip_id'  => $payslip->id,
            'status'      => LoanInstallmentStatus::Deducted,
            'deducted_at' => now(),
        ]);
    }
}
