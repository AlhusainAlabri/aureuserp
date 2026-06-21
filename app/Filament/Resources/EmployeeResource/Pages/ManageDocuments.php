<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Concerns\ExtendedEmployeeDocumentsRelation;
use App\Filament\Concerns\HasEmployeeRelatedPageTranslations;
use Filament\Resources\Pages\ManageRelatedRecords;
use Webkul\Employee\Filament\Resources\EmployeeResource;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\Concerns\HasEmployeeRecordNavigationTabs;

class ManageDocuments extends ManageRelatedRecords
{
    use ExtendedEmployeeDocumentsRelation;
    use HasEmployeeRecordNavigationTabs;
    use HasEmployeeRelatedPageTranslations;

    protected static string $resource = EmployeeResource::class;

    protected static string $relationship = 'documents';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function getNavigationLabel(): string
    {
        return __('employees::filament/resources/employee/pages/manage-documents.navigation.title');
    }

    protected static function employeeRelatedPageTranslationKey(): string
    {
        return 'employees::filament/resources/employee/pages/manage-documents';
    }
}
