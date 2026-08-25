<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Webkul\Payroll\Filament\Resources\EmployeeComponentResource;
use Webkul\Payroll\Filament\Resources\LoanResource;
use Webkul\Payroll\Filament\Resources\PayrollBatchResource;
use Webkul\Payroll\Filament\Resources\PayslipResource;
use Webkul\Payroll\Filament\Resources\SalaryComponentResource;
use Webkul\Payroll\Models\SalaryComponent;
use Webkul\Payroll\Support\PayrollCalendar;

it('uses arabic payroll plural labels instead of sales quotations', function (): void {
    app()->setLocale('ar');

    expect(PayrollBatchResource::getPluralModelLabel())->toBe('دفعات الرواتب')
        ->and(PayslipResource::getPluralModelLabel())->toBe('كشوف الرواتب')
        ->and(LoanResource::getPluralModelLabel())->toBe('قروض الموظفين')
        ->and(EmployeeComponentResource::getPluralModelLabel())->toBe('تعيينات الراتب')
        ->and(SalaryComponentResource::getPluralModelLabel())->toBe('مكونات الراتب');

    foreach ([
        PayrollBatchResource::getPluralModelLabel(),
        PayslipResource::getPluralModelLabel(),
        LoanResource::getPluralModelLabel(),
    ] as $label) {
        expect($label)->not->toContain('عروض الأسعار');
    }
});

it('uses arabic display name for salary component record title', function (): void {
    if (! Schema::hasTable('payroll_salary_components')) {
        $this->markTestSkipped('Payroll tables are not installed.');
    }

    app()->setLocale('ar');

    $component = SalaryComponent::factory()->create([
        'name'    => 'Basic Salary',
        'name_ar' => 'الراتب الأساسي',
    ]);

    expect($component->display_name)->toBe('الراتب الأساسي')
        ->and(SalaryComponentResource::getRecordTitle($component))->toBe('الراتب الأساسي');
});

it('provides arabic month labels for payroll batch period', function (): void {
    app()->setLocale('ar');

    $options = PayrollCalendar::monthOptions();

    expect($options[6])->toBe('يونيو')
        ->and(PayrollCalendar::formatPeriod(6, 2026))->toBe('يونيو 2026');
});

it('appends lang query to payroll resource urls', function (): void {
    if (! Schema::hasTable('payroll_salary_components')) {
        $this->markTestSkipped('Payroll tables are not installed.');
    }

    if (! Route::has('filament.admin.payroll.configuration.resources.salary-components.index')) {
        $this->markTestSkipped('Payroll Filament routes are not registered in this test context.');
    }

    app()->setLocale('ar');

    $url = SalaryComponentResource::getUrl('index');

    expect($url)->toContain('lang=ar');
});

it('provides complete arabic dashboard payroll widget translations', function (): void {
    app()->setLocale('ar');

    expect(__('dashboard.widgets.active_loans'))->toBe('القروض النشطة')
        ->and(__('dashboard.widgets.my_payslip'))->toBe('آخر كشف راتب')
        ->and(__('dashboard.stats.pending_batches'))->toBe('دفعات معلقة')
        ->and(__('dashboard.stats.active_loans'))->toBe('القروض النشطة');
});

it('provides arabic payroll email and my payslips translations', function (): void {
    app()->setLocale('ar');

    expect(__('payroll::payroll.email.subject', ['period' => 'يونيو 2026']))
        ->toContain('يونيو 2026')
        ->and(__('payroll::payroll.my_payslips.ytd'))->toBe('إجمالي الاستحقاقات منذ بداية السنة')
        ->and(__('payroll::payroll.reports.total_deductions'))->toBe('إجمالي الاستقطاعات');
});

it('formats payroll money based on locale', function (): void {
    app()->setLocale('ar');
    expect(SalaryComponentResource::formatMoney(1000.5))->toBe('ر.ع. 1,000.500');

    app()->setLocale('en');
    expect(SalaryComponentResource::formatMoney(1000.5))->toBe('OMR 1,000.500');
});
