<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Concerns\EmployeeTrainingsRelation;
use Filament\Resources\Pages\ManageRelatedRecords;
use Webkul\Employee\Filament\Resources\EmployeeResource;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\Concerns\HasEmployeeRecordNavigationTabs;

class ManageTrainings extends ManageRelatedRecords
{
    use EmployeeTrainingsRelation;
    use HasEmployeeRecordNavigationTabs;

    protected static string $resource = EmployeeResource::class;

    protected static string $relationship = 'trainings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    public static function getNavigationLabel(): string
    {
        return __('hr-extensions::training.navigation');
    }

    public function getTitle(): string
    {
        return static::getNavigationLabel();
    }
}
