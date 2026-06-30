<?php

namespace App\Filament\Projects\Pages;

use App\Enums\Projects\TaskPriorityLevel;
use App\Filament\Extensions\TaskResourceExtensions;
use App\Filament\Projects\Concerns\InteractsWithTaskFilters;
use App\Services\Projects\TaskNotificationService;
use App\Services\Projects\TaskStageHelper;
use App\Services\Projects\TaskStatePresenter;
use App\Support\FilamentUrl;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema as DbSchema;
use Webkul\Project\Enums\TaskState;
use Webkul\Project\Filament\Resources\TaskResource;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Task;
use Webkul\Project\Models\TaskStage;

class TaskKanban extends Page
{
    use HasPageShield;
    use InteractsWithTaskFilters;

    protected static string $routePath = 'projects/task-hub/kanban';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-view-columns';

    protected static ?int $navigationSort = 3;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.projects.pages.task-kanban';

    public static function getRoutePath(Panel $panel): string
    {
        return static::$routePath;
    }

    public function mount(): void
    {
        if (request()->filled('filterProjectId')) {
            $this->filterProjectId = (int) request()->query('filterProjectId');
        }
    }

    protected static function getPagePermission(): ?string
    {
        return 'page_task_kanban';
    }

    public static function canAccess(array $parameters = []): bool
    {
        return DbSchema::hasTable('projects_tasks')
            && DbSchema::hasTable('projects_task_stages')
            && parent::canAccess($parameters);
    }

    public function getTitle(): string
    {
        return __('tasks.kanban.title');
    }

    public function getSubheading(): ?string
    {
        return __('tasks.kanban.subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('hub')
                ->label(__('tasks.navigation.hub'))
                ->icon('heroicon-o-clipboard-document-check')
                ->url(fn (): string => FilamentUrl::appendLocaleToUrl(TaskOperationsHub::getUrl()))
                ->color('gray'),
            Action::make('calendar')
                ->label(__('tasks.hub.view_calendar'))
                ->icon('heroicon-o-calendar-days')
                ->url(fn (): string => FilamentUrl::appendLocaleToUrl(OperationsCalendar::getUrl()))
                ->color('gray'),
            Action::make('filters')
                ->label(__('tasks.filters.title'))
                ->icon('heroicon-o-funnel')
                ->schema($this->taskFilterSchema())
                ->fillForm(fn (): array => [
                    'filterEmployeeId'   => $this->filterEmployeeId,
                    'filterDepartmentId' => $this->filterDepartmentId,
                    'filterProjectId'    => $this->filterProjectId,
                    'filterCategoryId'   => $this->filterCategoryId,
                    'filterPriority'     => $this->filterPriority,
                ])
                ->action(function (array $data): void {
                    $this->filterEmployeeId = $data['filterEmployeeId'] ?? null;
                    $this->filterDepartmentId = $data['filterDepartmentId'] ?? null;
                    $this->filterProjectId = $data['filterProjectId'] ?? null;
                    $this->filterCategoryId = $data['filterCategoryId'] ?? null;
                    $this->filterPriority = $data['filterPriority'] ?? null;
                }),
            Action::make('clearFilters')
                ->label(__('tasks.filters.clear'))
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->action(fn (): mixed => $this->clearTaskFilters()),
            Action::make('createTask')
                ->label(__('tasks.hub.quick_create'))
                ->icon('heroicon-o-plus-circle')
                ->schema(TaskResourceExtensions::quickCreateSchema())
                ->modalWidth('4xl')
                ->action(function (array $data): void {
                    $prepared = TaskResourceExtensions::prepareQuickCreateData($data);
                    $users = $prepared['users'] ?? [];
                    unset($prepared['users']);

                    $task = new Task;
                    $task->forceFill($prepared);
                    $task->save();

                    if ($users !== []) {
                        $task->users()->sync($users);
                    }

                    app(TaskNotificationService::class)
                        ->notifyNewAssignment($task->fresh('users'));

                    Notification::make()
                        ->success()
                        ->title(__('tasks.notifications.task_created.title'))
                        ->body(__('tasks.notifications.task_created.body'))
                        ->send();
                })
                ->visible(fn (): bool => TaskResource::canCreate()),
        ];
    }

    /** @return Collection<int, TaskStage> */
    public function getStages(): Collection
    {
        $stages = TaskStage::query()
            ->when($this->filterProjectId, fn ($query) => $query->where(function ($inner): void {
                $inner->where('project_id', $this->filterProjectId)->orWhereNull('project_id');
            }))
            ->where('is_active', true)
            ->orderBy('sort')
            ->get();

        if ($stages->isEmpty() && $this->filterProjectId) {
            $project = Project::query()->find($this->filterProjectId);

            if ($project) {
                TaskStageHelper::seedDefaultsForProject($project);

                $stages = TaskStage::query()
                    ->where(function ($query): void {
                        $query->where('project_id', $this->filterProjectId)->orWhereNull('project_id');
                    })
                    ->where('is_active', true)
                    ->orderBy('sort')
                    ->get();
            }
        }

        return $stages;
    }

    /** @return array<int, Collection<int, Task>> */
    public function getTasksByStage(): array
    {
        $grouped = [];

        foreach ($this->getStages() as $stage) {
            $query = Task::query()
                ->with(['users', 'project'])
                ->whereNull('parent_id')
                ->where('stage_id', $stage->id)
                ->whereNotIn('state', [TaskState::DONE, TaskState::CANCELLED])
                ->orderBy('sort');

            $this->applyTaskFilters($query);

            $grouped[$stage->id] = $query->get();
        }

        return $grouped;
    }

    public function sortTask(string $id, int $position, ?string $group = null): void
    {
        $stageId = (int) ($group ?? 0);

        if ($stageId === 0) {
            return;
        }

        Task::query()
            ->whereKey($id)
            ->update([
                'stage_id' => $stageId,
                'sort'     => $position + 1,
            ]);
    }

    public function sortTasks(int $stageId, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $taskId) {
            Task::query()
                ->whereKey($taskId)
                ->update([
                    'stage_id' => $stageId,
                    'sort'     => $index + 1,
                ]);
        }
    }

    public function priorityColor(?string $priority): string
    {
        return TaskPriorityLevel::tryFrom((string) $priority)?->getColor() ?? 'gray';
    }

    public function priorityLabel(?string $priority): string
    {
        return TaskPriorityLevel::tryFrom((string) $priority)?->getLabel() ?? TaskPriorityLevel::Medium->getLabel();
    }

    public function taskUrl(Task $task): string
    {
        return FilamentUrl::appendLocaleToUrl(TaskResource::getUrl('view', ['record' => $task]));
    }

    public function isOverdue(Task $task): bool
    {
        return TaskStatePresenter::isOverdue($task);
    }
}
