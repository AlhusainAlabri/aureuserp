<?php

namespace Webkul\Payroll\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Enums\PayslipStatus;
use Webkul\Payroll\Filament\Resources\SalaryComponentResource;
use Webkul\Payroll\Models\Payslip;
use Webkul\Payroll\Services\PayslipPdfService;

class MyPayslips extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?int $navigationSort = 6;

    protected string $view = 'payroll::filament.pages.my-payslips';

    public static function getNavigationLabel(): string
    {
        return __('payroll::payroll.my_payslips.navigation');
    }

    public function getTitle(): string
    {
        return __('payroll::payroll.my_payslips.title');
    }

    public static function canAccess(): bool
    {
        return Auth::check();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::check();
    }

    #[Computed]
    public function payslips(): Collection
    {
        $employee = $this->getAuthEmployee();

        if (! $employee) {
            return collect();
        }

        return Payslip::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', [PayslipStatus::Validated, PayslipStatus::Paid])
            ->with('batch')
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->get();
    }

    public function downloadPayslip(int $payslipId): mixed
    {
        $employee = $this->getAuthEmployee();

        if (! $employee) {
            return null;
        }

        $payslip = Payslip::query()
            ->where('id', $payslipId)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        $path = app(PayslipPdfService::class)->generate($payslip);

        return Storage::disk('private')->download($path);
    }

    public function formatMoney(float $amount): string
    {
        return SalaryComponentResource::formatMoney($amount);
    }

    protected function getAuthEmployee(): ?Employee
    {
        return Employee::query()->where('user_id', Auth::id())->first();
    }
}
