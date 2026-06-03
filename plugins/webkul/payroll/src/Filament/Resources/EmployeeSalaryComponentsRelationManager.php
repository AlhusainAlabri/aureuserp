<?php

namespace Webkul\Payroll\Filament\Resources;

use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Support\Facades\Schema;
use Webkul\Payroll\Traits\EmployeeSalaryComponentsRelation;

class EmployeeSalaryComponentsRelationManager extends RelationManager
{
    use EmployeeSalaryComponentsRelation;

    protected static string $relationship = 'employeeComponents';

    public static function getTitle($ownerRecord = null, ?string $pageClass = null): string
    {
        return __('payroll::payroll.relations.employee_components');
    }

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return Schema::hasTable('payroll_employee_components');
    }
}
