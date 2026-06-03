<?php

namespace App\Filament\Projects\Pages;

use App\Filament\Concerns\InteractsWithAdvancedDashboard;
use App\Filament\Projects\Widgets\TaskOverviewStatsWidget;
use App\Filament\Projects\Widgets\TasksByStatusChartWidget;
use App\Filament\Projects\Widgets\TaskWorkloadWidget;
use App\Support\FilamentUrl;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Schema;
use Webkul\Project\Filament\Clusters\Configurations\Resources\TaskStageResource;
use Webkul\Project\Filament\Resources\TaskResource;

class TaskOperationsHub extends BaseDashboard
{
    use HasPageShield;
    use InteractsWithAdvancedDashboard;

    protected static string $routePath = 'projects/task-hub';

    protected string $view = 'filament.pages.advanced-dashboard';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 2;

    protected ?string $pollingInterval = null;

    protected static function getPagePermission(): ?string
    {
        return 'page_task_operations_hub';
    }

    public static function getNavigationLabel(): string
    {
        return __('tasks.navigation.hub');
    }

    public static function getNavigationGroup(): string
    {
        return __('projects::filament/resources/task.navigation.group');
    }

    public static function canAccess(array $parameters = []): bool
    {
        return Schema::hasTable('projects_tasks')
            && parent::canAccess($parameters);
    }

    public function getTitle(): string
    {
        return __('tasks.hub.title');
    }

    public function getSubheading(): ?string
    {
        return __('tasks.hub.subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('taskStages')
                ->label(__('projects::filament/clusters/configurations/resources/task-stage.navigation.title'))
                ->icon('heroicon-o-rectangle-stack')
                ->url(fn (): string => FilamentUrl::appendLocaleToUrl(TaskStageResource::getUrl()))
                ->visible(fn (): bool => TaskStageResource::canAccess())
                ->color('gray'),
            Action::make('listView')
                ->label(__('tasks.hub.view_list'))
                ->icon('heroicon-o-list-bullet')
                ->url(fn (): string => FilamentUrl::appendLocaleToUrl(TaskResource::getUrl('index')))
                ->color('gray'),
            Action::make('kanbanView')
                ->label(__('tasks.hub.view_kanban'))
                ->icon('heroicon-o-view-columns')
                ->url(fn (): string => FilamentUrl::appendLocaleToUrl(TaskKanban::getUrl()))
                ->color('gray'),
            Action::make('calendarView')
                ->label(__('tasks.hub.view_calendar'))
                ->icon('heroicon-o-calendar-days')
                ->url(fn (): string => FilamentUrl::appendLocaleToUrl(OperationsCalendar::getUrl()))
                ->color('gray'),
            Action::make('createTask')
                ->label(__('tasks.hub.quick_create'))
                ->icon('heroicon-o-plus-circle')
                ->url(fn (): string => FilamentUrl::appendLocaleToUrl(TaskResource::getUrl('create')))
                ->visible(fn (): bool => TaskResource::canCreate())
                ->color('primary'),
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            TaskOverviewStatsWidget::class,
        ];
    }

    public function getWidgets(): array
    {
        return [
            TasksByStatusChartWidget::class,
            TaskWorkloadWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md'      => 2,
            'lg'      => 12,
        ];
    }
}
