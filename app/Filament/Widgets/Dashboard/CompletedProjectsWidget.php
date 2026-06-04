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

class CompletedProjectsWidget extends BaseWidget
{
    use BuildsEmptyTableQueries;
    use HasOrgDashboardTableLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 13;

    protected static bool $isLazy = true;

    protected static ?string $pollingInterval = null;

    public function getTableHeading(): string|Htmlable|null
    {
        return __('projects-extensions::widgets.completed_projects');
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
                        fn ($query) => ProjectStageHelper::applyStageFilter($query, 'done'),
                        fn ($query) => $query->where('is_active', false),
                    )
                    ->orderByDesc('end_date')
                    ->limit(8)
            )
            ->columns([
                TextColumn::make('name')
                    ->label(__('dashboard.table.name'))
                    ->limit(30),

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
                    ->date('d M Y'),
            ])
            ->emptyStateIcon('heroicon-o-check-circle')
            ->emptyStateHeading(__('dashboard.empty.no_completed_projects'))
            ->emptyStateDescription(__('dashboard.empty.no_completed_projects_description'))
            ->paginated(false);
    }
}
