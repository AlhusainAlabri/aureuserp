<?php

namespace App\Filament\Projects\Concerns;

use App\Enums\Projects\TaskPriorityLevel;
use App\Models\Projects\TaskCategory;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Models\Department;
use Webkul\Project\Models\Project;
use Webkul\Security\Models\User;

trait InteractsWithTaskFilters
{
    public ?int $filterEmployeeId = null;

    public ?int $filterDepartmentId = null;

    public ?int $filterProjectId = null;

    public ?int $filterCategoryId = null;

    public ?string $filterPriority = null;

    public function applyTaskFilters($query)
    {
        if ($this->filterProjectId) {
            $query->where('project_id', $this->filterProjectId);
        }

        if ($this->filterCategoryId && Schema::hasColumn('projects_tasks', 'category_id')) {
            $query->where('category_id', $this->filterCategoryId);
        }

        if ($this->filterPriority && Schema::hasColumn('projects_tasks', 'priority_level')) {
            $query->where('priority_level', $this->filterPriority);
        }

        if ($this->filterDepartmentId && Schema::hasColumn('projects_tasks', 'department_id')) {
            $query->where('department_id', $this->filterDepartmentId);
        }

        if ($this->filterEmployeeId) {
            $query->where(function ($innerQuery): void {
                $innerQuery
                    ->whereHas('users', fn ($assigneeQuery) => $assigneeQuery->where('user_id', $this->filterEmployeeId))
                    ->when(
                        Schema::hasColumn('projects_tasks', 'owner_id'),
                        fn ($ownerQuery) => $ownerQuery->orWhere('owner_id', $this->filterEmployeeId),
                    );
            });
        }

        return $query;
    }

    /** @return array<int, Select> */
    protected function taskFilterSchema(): array
    {
        return [
            Select::make('filterEmployeeId')
                ->label(__('tasks.filters.employee'))
                ->searchable()
                ->getSearchResultsUsing(fn (string $search): array => User::query()
                    ->where('name', 'like', "%{$search}%")
                    ->limit(50)
                    ->pluck('name', 'id')
                    ->all())
                ->getOptionLabelUsing(fn ($value): ?string => User::query()->find($value)?->name)
                ->live(),
            Select::make('filterDepartmentId')
                ->label(__('tasks.filters.department'))
                ->searchable()
                ->getSearchResultsUsing(fn (string $search): array => Schema::hasTable('employees_departments')
                    ? Department::query()
                        ->where('name', 'like', "%{$search}%")
                        ->limit(50)
                        ->pluck('name', 'id')
                        ->all()
                    : [])
                ->getOptionLabelUsing(fn ($value): ?string => Department::query()->find($value)?->name)
                ->visible(fn (): bool => Schema::hasTable('employees_departments'))
                ->live(),
            Select::make('filterProjectId')
                ->label(__('tasks.filters.project'))
                ->searchable()
                ->getSearchResultsUsing(fn (string $search): array => Project::query()
                    ->where('name', 'like', "%{$search}%")
                    ->limit(50)
                    ->pluck('name', 'id')
                    ->all())
                ->getOptionLabelUsing(fn ($value): ?string => Project::query()->find($value)?->name)
                ->live(),
            Select::make('filterCategoryId')
                ->label(__('tasks.filters.category'))
                ->searchable()
                ->getSearchResultsUsing(fn (string $search): array => Schema::hasTable('projects_task_categories')
                    ? TaskCategory::query()
                        ->where('is_active', true)
                        ->where('name', 'like', "%{$search}%")
                        ->limit(50)
                        ->pluck('name', 'id')
                        ->all()
                    : [])
                ->getOptionLabelUsing(fn ($value): ?string => TaskCategory::query()->find($value)?->name)
                ->visible(fn (): bool => Schema::hasTable('projects_task_categories'))
                ->live(),
            Select::make('filterPriority')
                ->label(__('tasks.filters.priority'))
                ->options(collect(TaskPriorityLevel::cases())->mapWithKeys(
                    fn (TaskPriorityLevel $priority): array => [$priority->value => $priority->getLabel()],
                )->all())
                ->visible(fn (): bool => Schema::hasColumn('projects_tasks', 'priority_level'))
                ->live(),
        ];
    }

    public function clearTaskFilters(): void
    {
        $this->filterEmployeeId = null;
        $this->filterDepartmentId = null;
        $this->filterProjectId = null;
        $this->filterCategoryId = null;
        $this->filterPriority = null;
    }
}
