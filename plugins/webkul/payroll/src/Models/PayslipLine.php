<?php

namespace Webkul\Payroll\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Payroll\Database\Factories\PayslipLineFactory;
use Webkul\Payroll\Enums\SalaryComponentType;

class PayslipLine extends Model
{
    use HasFactory;

    protected $table = 'payroll_payslip_lines';

    protected $fillable = [
        'payslip_id',
        'component_id',
        'type',
        'code',
        'name',
        'quantity',
        'rate',
        'amount',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type'     => SalaryComponentType::class,
            'quantity' => 'decimal:2',
            'rate'     => 'decimal:3',
            'amount'   => 'decimal:3',
        ];
    }

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class, 'payslip_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class, 'component_id');
    }

    public function isLocked(): bool
    {
        return $this->payslip?->isValidated() ?? false;
    }

    protected static function newFactory(): PayslipLineFactory
    {
        return PayslipLineFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::updating(function (self $line): void {
            if ($line->payslip?->isValidated()) {
                throw new \RuntimeException(__('payroll::exceptions.payslip_line_locked'));
            }
        });

        static::deleting(function (self $line): void {
            if ($line->payslip?->isValidated()) {
                throw new \RuntimeException(__('payroll::exceptions.payslip_line_locked'));
            }
        });
    }
}
