<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Concerns\HasEmployeeRelatedPageTranslations;
use Filament\Resources\Pages\ManageRelatedRecords;
use Webkul\Employee\Filament\Resources\EmployeeResource;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\Concerns\HasEmployeeRecordNavigationTabs;
use Webkul\Employee\Traits\Resources\Employee\EmployeeWarningsRelation;

class ManageWarnings extends ManageRelatedRecords
{
    use EmployeeWarningsRelation;
    use HasEmployeeRecordNavigationTabs;
    use HasEmployeeRelatedPageTranslations;

    protected static string $resource = EmployeeResource::class;

    protected static string $relationship = 'warnings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    public static function getNavigationLabel(): string
    {
        return __('employees::filament/resources/employee/pages/manage-warnings.navigation.title');
    }

    protected static function employeeRelatedPageTranslationKey(): string
    {
        return 'employees::filament/resources/employee/pages/manage-warnings';
    }
}
