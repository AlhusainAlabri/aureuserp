<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Concerns\EmployeeSalaryRaisesRelation;
use App\Filament\Widgets\Employee\EmployeeSalaryHistoryChartWidget;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;
use Webkul\Employee\Filament\Resources\EmployeeResource;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\Concerns\HasEmployeeRecordNavigationTabs;

class ManageSalaryRaises extends ManageRelatedRecords
{
    use EmployeeSalaryRaisesRelation;
    use HasEmployeeRecordNavigationTabs;

    protected static string $resource = EmployeeResource::class;

    protected static string $relationship = 'salaryRaises';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    public static function getNavigationLabel(): string
    {
        return __('hr-extensions::salary_raise.navigation');
    }

    public function getTitle(): string
    {
        return static::getNavigationLabel();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Livewire::make(EmployeeSalaryHistoryChartWidget::class, fn (): array => [
                    'employeeId' => $this->getOwnerRecord()->id,
                ])->columnSpanFull(),
                Group::make([
                    $this->getTabsContentComponent(),
                    RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_MANAGE_RELATED_RECORDS_TABLE_BEFORE),
                    EmbeddedTable::make(),
                    RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_MANAGE_RELATED_RECORDS_TABLE_AFTER),
                ])->visible(! empty($this->getTable()->getColumns())),
                $this->getRelationManagersContentComponent(),
            ]);
    }
}
