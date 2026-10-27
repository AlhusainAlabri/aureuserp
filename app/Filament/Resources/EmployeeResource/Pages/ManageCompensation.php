<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Concerns\EmployeeCompensationRelation;
use App\Support\SchemaMemo;
use Filament\Resources\Pages\ManageRelatedRecords;
use Webkul\Employee\Filament\Resources\EmployeeResource;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\Concerns\HasEmployeeRecordNavigationTabs;
use Webkul\Payroll\Filament\Resources\EmployeeComponentResource;

class ManageCompensation extends ManageRelatedRecords
{
    use EmployeeCompensationRelation;
    use HasEmployeeRecordNavigationTabs;

    protected static string $resource = EmployeeResource::class;

    protected static string $relationship = 'employeeComponents';

    protected static ?string $relatedResource = EmployeeComponentResource::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    public static function getNavigationLabel(): string
    {
        return __('hr-extensions::compensation.navigation');
    }

    public function getTitle(): string
    {
        return static::getNavigationLabel();
    }

    public static function canAccess(array $parameters = []): bool
    {
        return SchemaMemo::hasTable('payroll_employee_components');
    }
}
