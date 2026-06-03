<?php

namespace Webkul\Employee\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Webkul\Employee\Traits\Resources\Employee\EmployeeDocumentsRelation;

class DocumentsRelationManager extends RelationManager
{
    use EmployeeDocumentsRelation;

    protected static string $relationship = 'documents';
}
