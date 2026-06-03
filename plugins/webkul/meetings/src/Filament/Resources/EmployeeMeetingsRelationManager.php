<?php

namespace Webkul\Meetings\Filament\Resources;

use Filament\Resources\RelationManagers\RelationManager;
use Webkul\Meetings\Traits\EmployeeMeetingsRelation;

class EmployeeMeetingsRelationManager extends RelationManager
{
    use EmployeeMeetingsRelation;

    protected static string $relationship = 'meetingAttendances';

    public static function getTitle($ownerRecord = null, ?string $pageClass = null): string
    {
        return __('meetings::meetings.relations.employee_meetings');
    }
}
