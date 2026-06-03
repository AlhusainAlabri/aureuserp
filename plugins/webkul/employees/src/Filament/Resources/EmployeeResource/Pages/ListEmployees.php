<?php

namespace Webkul\Employee\Filament\Resources\EmployeeResource\Pages;

use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Webkul\Employee\Filament\Resources\EmployeeResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListEmployees extends ListRecords
{
    use HasTableViews;

    protected static string $resource = EmployeeResource::class;

    #[Url(as: 'layout')]
    public string $tableLayout = 'grid';

    public function table(Table $table): Table
    {
        $table = parent::table($table);

        if ($this->usesGridLayout()) {
            return $table
                ->columns(EmployeeResource::getCardTableColumns())
                ->contentGrid([
                    'md' => 2,
                    'xl' => 4,
                ]);
        }

        return $table
            ->columns(EmployeeResource::getListTableColumns())
            ->contentGrid(null);
    }

    public function setTableLayout(string $layout): void
    {
        $this->tableLayout = $layout === 'table' ? 'table' : 'grid';

        $this->resetTable();
    }

    protected function usesGridLayout(): bool
    {
        return $this->tableLayout !== 'table';
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('gridLayout')
                    ->label(__('employees::filament/resources/employee/pages/list-employee.header-actions.layout.grid'))
                    ->icon('heroicon-o-squares-2x2')
                    ->action(fn (): mixed => $this->setTableLayout('grid'))
                    ->color(fn (): string => $this->usesGridLayout() ? 'primary' : 'gray'),
                Action::make('tableLayout')
                    ->label(__('employees::filament/resources/employee/pages/list-employee.header-actions.layout.table'))
                    ->icon('heroicon-o-table-cells')
                    ->action(fn (): mixed => $this->setTableLayout('table'))
                    ->color(fn (): string => $this->usesGridLayout() ? 'gray' : 'primary'),
            ])
                ->label(__('employees::filament/resources/employee/pages/list-employee.header-actions.layout.label'))
                ->icon('heroicon-o-view-columns')
                ->button()
                ->color('gray'),
            CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->label(__('employees::filament/resources/employee/pages/list-employee.header-actions.create.label')),
        ];
    }

    public function getPresetTableViews(): array
    {
        return [
            'my_team' => PresetView::make(__('employees::filament/resources/employee/pages/list-employee.tabs.my-team'))
                ->icon('heroicon-m-users')
                ->favorite()
                ->modifyQueryUsing(function (Builder $query) {
                    $user = Auth::user();

                    if (! $user->employee) {
                        return $query->whereNull('id');
                    }

                    return $query->where('parent_id', $user->employee->id);
                }),

            'my_department' => PresetView::make(__('employees::filament/resources/employee/pages/list-employee.tabs.my-department'))
                ->icon('heroicon-m-user-group')
                ->favorite()
                ->modifyQueryUsing(function (Builder $query) {
                    $user = Auth::user();

                    if (! $user->employee) {
                        return $query->whereNull('id');
                    }

                    return $query->where('department_id', $user->employee->department_id);
                }),

            'compliance_issues' => PresetView::make(__('employees::filament/resources/employee/pages/list-employee.tabs.compliance-issues'))
                ->icon('heroicon-m-shield-exclamation')
                ->favorite()
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->withComplianceIssues()),

            'incomplete_profile' => PresetView::make(__('employees::filament/resources/employee/pages/list-employee.tabs.incomplete-profile'))
                ->icon('heroicon-m-user-minus')
                ->favorite()
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->incompleteProfile()),

            'archived' => PresetView::make(__('employees::filament/resources/employee/pages/list-employee.tabs.archived'))
                ->icon('heroicon-s-archive-box')
                ->favorite()
                ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed()),

            'newly_hired' => PresetView::make(__('employees::filament/resources/employee/pages/list-employee.tabs.newly-hired'))
                ->icon('heroicon-s-calendar')
                ->favorite()
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where('created_at', '>=', Carbon::now()->subMonth());
                }),
        ];
    }
}
