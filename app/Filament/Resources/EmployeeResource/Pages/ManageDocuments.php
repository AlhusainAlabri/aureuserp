<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Concerns\ExtendedEmployeeDocumentsRelation;
use Filament\Resources\Pages\ManageRelatedRecords;
use Webkul\Employee\Filament\Resources\EmployeeResource;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\Concerns\HasEmployeeRecordNavigationTabs;

class ManageDocuments extends ManageRelatedRecords
{
    use ExtendedEmployeeDocumentsRelation;
    use HasEmployeeRecordNavigationTabs;

    protected static string $resource = EmployeeResource::class;

    protected static string $relationship = 'documents';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function getNavigationLabel(): string
    {
        return __('employees::filament/resources/employee/pages/manage-documents.navigation.title');
    }

    public function getTitle(): string
    {
        return static::getNavigationLabel();
    }
}
