<?php

namespace App\Filament\Widgets\Dashboard;

use App\Support\SchemaMemo;
use Filament\Widgets\Widget;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Enums\PayslipStatus;
use Webkul\Payroll\Filament\Pages\MyPayslips;
use Webkul\Payroll\Filament\Resources\SalaryComponentResource;
use Webkul\Payroll\Models\Payslip;
use Webkul\Payroll\Services\PayslipPdfService;
use Webkul\Payroll\Support\PayrollCalendar;

class MyPayslipWidget extends Widget
{
    protected static ?int $sort = 9;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    protected string $view = 'filament.widgets.dashboard.my-payslip';

    public ?Payslip $latestPayslip = null;

    public function mount(): void
    {
        if (! SchemaMemo::hasTable('payroll_payslips')) {
            return;
        }

        $employee = Employee::query()->where('user_id', auth()->id())->first();

        if (! $employee) {
            return;
        }

        $this->latestPayslip = Payslip::query()
            ->where('employee_id', $employee->id)
            ->where('status', PayslipStatus::Paid)
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->first();
    }

    public function downloadPdf(): mixed
    {
        if (! $this->latestPayslip) {
            return null;
        }

        $path = app(PayslipPdfService::class)->generate($this->latestPayslip);

        return response()->download(
            storage_path('app/private/'.$path),
            __('dashboard.payslip_filename', ['reference' => $this->latestPayslip->reference_number]),
        );
    }

    public function getMyPayslipsUrl(): string
    {
        return MyPayslips::getUrl();
    }

    public function formatMoney(float $amount): string
    {
        return SalaryComponentResource::formatMoney($amount);
    }

    public function formatPeriod(Payslip $payslip): string
    {
        return PayrollCalendar::formatPeriod((int) $payslip->period_month, (int) $payslip->period_year);
    }
}
