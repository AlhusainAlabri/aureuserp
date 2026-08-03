<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\BuildsEmptyTableQueries;
use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardTableLayout;
use App\Services\Projects\ProjectStageHelper;
use App\Support\SchemaMemo;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Support\Htmlable;
use Webkul\Project\Enums\TaskState;
use Webkul\Project\Models\Project;

class ActiveProjectsWidget extends BaseWidget
{
    use BuildsEmptyTableQueries;
    use HasOrgDashboardTableLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 12;

    protected static ?string $pollingInterval = null;

    protected static bool $isLazy = true;

    public function getTableHeading(): string|Htmlable|null
    {
        return __('dashboard.widgets.active_projects');
    }

    public function table(Table $table): Table
    {
        if (! SchemaMemo::hasTable('projects_projects')) {
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
                    ->withCount([
                        'tasks as active_tasks_count' => fn ($query) => $query
                            ->whereNull('parent_id')
                            ->where('state', '!=', TaskState::CANCELLED->value),
                        'tasks as done_tasks_count' => fn ($query) => $query
                            ->whereNull('parent_id')
                            ->where('state', TaskState::DONE->value),
                        'milestones as total_milestones_count',
                        'milestones as completed_milestones_count' => fn ($query) => $query
                            ->where('is_completed', true),
                    ])
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
                    ->state(function (Project $record): string {
                        if ($record->active_tasks_count > 0) {
                            $percentage = round(($record->done_tasks_count / $record->active_tasks_count) * 100, 1);

                            return number_format($percentage, 1).'%';
                        }

                        if ($record->total_milestones_count > 0) {
                            $percentage = round(($record->completed_milestones_count / $record->total_milestones_count) * 100, 1);

                            return number_format($percentage, 1).'%';
                        }

                        return '0.0%';
                    }),

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
