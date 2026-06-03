<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\BuildsEmptyTableQueries;
use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardTableLayout;
use App\Services\Projects\ProjectCompletionService;
use App\Services\Projects\ProjectStageHelper;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Schema;
use Webkul\Project\Models\Project;

class ActiveProjectsWidget extends BaseWidget
{
    use BuildsEmptyTableQueries;
    use HasOrgDashboardTableLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 12;

    protected static ?string $pollingInterval = null;

    public function getTableHeading(): string|Htmlable|null
    {
        return __('dashboard.widgets.active_projects');
    }

    public function table(Table $table): Table
    {
        if (! Schema::hasTable('projects_projects')) {
            return $table
                ->query($this->emptyTableQuery(Project::class, [
                    'id'         => '0',
                    'name'       => "''",
                    'end_date'   => 'null',
                    'deleted_at' => 'null',
                ]))
                ->columns([TextColumn::make('name')])
                ->emptyStateHeading(__('dashboard.plugin_not_installed'));
        }

        return $table
            ->query(
                Project::query()
                    ->with(['stage', 'user'])
                    ->when(
                        ProjectStageHelper::isAvailable(),
                        fn ($query) => ProjectStageHelper::applyStageFilter($query->where('is_active', true), 'in_progress'),
                        fn ($query) => $query->where('is_active', true),
                    )
                    ->orderBy('end_date')
                    ->limit(8)
            )
            ->columns([
                TextColumn::make('name')
                    ->label(__('dashboard.table.name'))
                    ->limit(30)
                    ->searchable(false),

                TextColumn::make('stage.name')
                    ->label(__('projects-extensions::columns.stage'))
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('completion_percentage')
                    ->label(__('projects-extensions::columns.completion'))
                    ->state(fn (Project $record): string => app(ProjectCompletionService::class)->formatPercentage(
                        app(ProjectCompletionService::class)->calculate($record),
                    )),

                TextColumn::make('user.name')
                    ->label(__('projects-extensions::columns.manager'))
                    ->placeholder('—'),

                TextColumn::make('end_date')
                    ->label(__('dashboard.table.due_date'))
                    ->date('d M Y')
                    ->color(fn ($record) => $record->end_date && $record->end_date->isPast() ? 'danger' : null)
                    ->searchable(false),
            ])
            ->emptyStateIcon('heroicon-o-folder')
            ->emptyStateHeading(__('dashboard.empty.no_projects'))
            ->emptyStateDescription(__('dashboard.empty.no_projects_description'))
            ->paginated(false);
    }
}
