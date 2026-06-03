<?php

namespace App\Filament\Projects\Widgets;

use App\Filament\Projects\Widgets\Concerns\HasTaskHubWidgetLayout;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Schema;
use Webkul\Security\Models\User;

class TaskWorkloadWidget extends BaseWidget
{
    use HasTaskHubWidgetLayout;

    protected static ?int $sort = 3;

    protected ?string $pollingInterval = null;

    public function getTableHeading(): ?string
    {
        return __('tasks.stats.workload');
    }

    public function table(Table $table): Table
    {
        if (! Schema::hasTable('projects_tasks') || ! Schema::hasTable('projects_task_users')) {
            return $table
                ->query(fn () => User::query()->whereRaw('1 = 0'))
                ->columns([
                    TextColumn::make('name')->label(__('tasks.filters.employee')),
                ])
                ->emptyStateHeading(__('dashboard.plugin_not_installed'));
        }

        return $table
            ->query(
                User::query()
                    ->select('users.*')
                    ->selectSub(function ($query): void {
                        $query->from('projects_task_users')
                            ->join('projects_tasks', 'projects_tasks.id', '=', 'projects_task_users.task_id')
                            ->whereColumn('projects_task_users.user_id', 'users.id')
                            ->whereNull('projects_tasks.deleted_at')
                            ->whereNull('projects_tasks.parent_id')
                            ->whereNotIn('projects_tasks.state', ['done', 'cancelled'])
                            ->selectRaw('count(distinct projects_tasks.id)');
                    }, 'open_tasks_count')
                    ->having('open_tasks_count', '>', 0)
                    ->orderByDesc('open_tasks_count')
                    ->limit(8),
            )
            ->columns([
                TextColumn::make('name')
                    ->label(__('tasks.filters.employee'))
                    ->searchable(false),
                TextColumn::make('open_tasks_count')
                    ->label(__('tasks.stats.open'))
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 10 => 'danger',
                        $state >= 5  => 'warning',
                        default      => 'info',
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading(__('tasks.empty.no_workload'))
            ->emptyStateDescription(__('tasks.empty.no_workload_description'))
            ->emptyStateIcon('heroicon-o-user-group');
    }
}
