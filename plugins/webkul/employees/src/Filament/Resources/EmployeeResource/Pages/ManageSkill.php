<?php

namespace Webkul\Employee\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Concerns\HasEmployeeRelatedPageTranslations;
use Filament\Resources\Pages\ManageRelatedRecords;
use Webkul\Employee\Filament\Resources\EmployeeResource;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\Concerns\HasEmployeeRecordNavigationTabs;
use Webkul\Employee\Traits\Resources\Employee\EmployeeSkillRelation;

class ManageSkill extends ManageRelatedRecords
{
    use EmployeeSkillRelation;
    use HasEmployeeRecordNavigationTabs;
    use HasEmployeeRelatedPageTranslations;

    protected static string $resource = EmployeeResource::class;

    protected static string $relationship = 'skills';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    public static function getNavigationLabel(): string
    {
        return __('employees::filament/resources/employee/pages/manage-skill.navigation.title');
    }

    protected static function employeeRelatedPageTranslationKey(): string
    {
        return 'employees::filament/resources/employee/pages/manage-skill';
    }
}
