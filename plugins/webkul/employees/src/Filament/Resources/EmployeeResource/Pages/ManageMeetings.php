<?php

namespace Webkul\Employee\Filament\Resources\EmployeeResource\Pages;

use Filament\Resources\Pages\ManageRelatedRecords;
use Webkul\Employee\Filament\Resources\EmployeeResource;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\Concerns\HasEmployeeRecordNavigationTabs;
use Webkul\Meetings\Traits\EmployeeMeetingsRelation;

class ManageMeetings extends ManageRelatedRecords
{
    use EmployeeMeetingsRelation;
    use HasEmployeeRecordNavigationTabs;

    protected static string $resource = EmployeeResource::class;

    protected static string $relationship = 'meetingAttendances';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    public static function getNavigationLabel(): string
    {
        return __('employees::filament/resources/employee/pages/manage-meetings.navigation.title');
    }
}
