<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Concerns\EmployeeContractsRelation;
use Filament\Resources\Pages\ManageRelatedRecords;
use Webkul\Employee\Filament\Resources\EmployeeResource;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\Concerns\HasEmployeeRecordNavigationTabs;

class ManageContracts extends ManageRelatedRecords
{
    use EmployeeContractsRelation;
    use HasEmployeeRecordNavigationTabs;

    protected static string $resource = EmployeeResource::class;

    protected static string $relationship = 'contracts';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    public static function getNavigationLabel(): string
    {
        return __('hr-extensions::contract.navigation');
    }

    public function getBreadcrumb(): string
    {
        return static::getNavigationLabel();
    }

    public function getTitle(): string
    {
        return static::getNavigationLabel();
    }
}
