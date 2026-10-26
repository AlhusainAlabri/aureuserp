<?php

namespace Webkul\Payroll\Filament\Resources\EmployeeComponentResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Filament\Resources\EmployeeComponentResource;
use Webkul\Payroll\Services\SalaryStructureAssigner;

class ListEmployeeComponents extends ListRecords
{
    protected static string $resource = EmployeeComponentResource::class;

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->emptyStateHeading(__('payroll::payroll.table.empty'));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('assign_from_contract')
                ->label(__('payroll::payroll.assign_from_contract.label'))
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->visible(fn (): bool => EmployeeComponentResource::canCreate())
                ->schema([
                    Select::make('employee_id')
                        ->label(__('payroll::payroll.assign_from_contract.employee'))
                        ->helperText(__('payroll::payroll.assign_from_contract.help'))
                        ->options(fn (): array => Employee::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $employee = Employee::query()->findOrFail($data['employee_id']);
                    $assigner = app(SalaryStructureAssigner::class);

                    if ($assigner->activeContract($employee) === null) {
                        Notification::make()->danger()
                            ->title(__('payroll::payroll.assign_from_contract.no_contract'))
                            ->send();

                        return;
                    }

                    $assigned = $assigner->assignFromContract($employee);

                    Notification::make()
                        ->{$assigned === [] ? 'warning' : 'success'}()
                        ->title(__($assigned === []
                            ? 'payroll::payroll.assign_from_contract.nothing'
                            : 'payroll::payroll.assign_from_contract.done', ['codes' => implode('، ', $assigned)]))
                        ->send();
                }),
            CreateAction::make()->icon('heroicon-o-plus'),
        ];
    }
}
