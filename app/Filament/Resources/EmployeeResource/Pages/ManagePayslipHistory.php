<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Concerns\EmployeePayslipHistoryRelation;
use Filament\Resources\Pages\ManageRelatedRecords;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Filament\Resources\EmployeeResource;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\Concerns\HasEmployeeRecordNavigationTabs;

class ManagePayslipHistory extends ManageRelatedRecords
{
    use EmployeePayslipHistoryRelation;
    use HasEmployeeRecordNavigationTabs;

    protected static string $resource = EmployeeResource::class;

    protected static string $relationship = 'payslips';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    public static function getNavigationLabel(): string
    {
        return __('hr-extensions::payslip_history.navigation');
    }

    public function getTitle(): string
    {
        return static::getNavigationLabel();
    }

    public static function canAccess(array $parameters = []): bool
    {
        return Schema::hasTable('payroll_payslips');
    }
}
