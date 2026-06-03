<?php

namespace App\Filament\Widgets\Dashboard;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Enums\PayslipStatus;
use Webkul\Payroll\Filament\Pages\MyPayslips;
use Webkul\Payroll\Models\Payslip;
use Webkul\Payroll\Services\PayslipPdfService;

class MyPayslipWidget extends Widget
{
    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = 1;

    protected string $view = 'filament.widgets.dashboard.my-payslip';

    public ?Payslip $latestPayslip = null;

    public function mount(): void
    {
        if (! Schema::hasTable('payroll_payslips')) {
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
            'Payslip_'.$this->latestPayslip->reference_number.'.pdf',
        );
    }

    public function getMyPayslipsUrl(): string
    {
        return MyPayslips::getUrl();
    }
}
