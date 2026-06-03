<?php

namespace Webkul\Employee\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Webkul\Employee\Traits\Resources\Employee\EmployeeWarningsRelation;

class WarningsRelationManager extends RelationManager
{
    use EmployeeWarningsRelation;

    protected static string $relationship = 'warnings';
}
