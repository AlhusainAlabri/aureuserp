<?php

namespace App\Filament\Extensions;

use App\Enums\Projects\TaskPriorityLevel;
use App\Models\Projects\TaskCategory;
use App\Services\Projects\TaskStatePresenter;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Models\Department;
use Webkul\Project\Enums\TaskState;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Task;
use Webkul\Project\Models\TaskStage;
use Webkul\Security\Models\User;

class TaskResourceExtensions
{
    public static function getModelLabel(): string
    {
        return __('projects::filament/resources/task.navigation.title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('projects::filament/resources/task.title');
    }

    public static function featuredPriorityColumnLabel(): string
    {
        return self::extendedFieldsAvailable()
            ? __('tasks.columns.featured')
            : __('projects::filament/resources/task.table.columns.priority');
    }

    public static function extendedFieldsAvailable(): bool
    {
        return Schema::hasTable('projects_tasks')
            && Schema::hasColumn('projects_tasks', 'priority_level');
    }

    /** @return array<int, mixed> */
    public static function quickCreateSchema(): array
    {
        $schema = [
            TextInput::make('title')
                ->label(__('projects::filament/resources/task.form.sections.general.fields.title'))
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Select::make('project_id')
                ->label(__('projects::filament/resources/task.form.sections.settings.fields.project'))
                ->searchable()
                ->getSearchResultsUsing(fn (string $search): array => Project::query()
                    ->where('name', 'like', "%{$search}%")
                    ->limit(50)
                    ->pluck('name', 'id')
                    ->all())
                ->getOptionLabelUsing(fn ($value): ?string => Project::query()->find($value)?->name)
                ->columnSpan(1),
            Select::make('users')
                ->label(__('projects::filament/resources/task.form.sections.settings.fields.assignees'))
                ->multiple()
                ->searchable()
                ->getSearchResultsUsing(fn (string $search): array => User::query()
                    ->where('name', 'like', "%{$search}%")
                    ->limit(50)
                    ->pluck('name', 'id')
                    ->all())
                ->getOptionLabelsUsing(fn (array $values): array => User::query()
                    ->whereIn('id', $values)
                    ->pluck('name', 'id')
                    ->all())
                ->columnSpan(1),
            DateTimePicker::make('deadline')
                ->label(__('projects::filament/resources/task.form.sections.settings.fields.deadline'))
                ->native(false)
                ->suffixIcon('heroicon-o-calendar')
                ->columnSpan(1),
        ];

        if (self::extendedFieldsAvailable()) {
            $schema[] = ToggleButtons::make('priority_level')
                ->label(__('tasks.fields.priority_level'))
                ->options(TaskPriorityLevel::class)
                ->default(TaskPriorityLevel::Medium->value)
                ->inline()
                ->columnSpan(1);

            $schema[] = Select::make('owner_id')
                ->label(__('tasks.fields.owner'))
                ->searchable()
                ->getSearchResultsUsing(fn (string $search): array => User::query()
                    ->where('name', 'like', "%{$search}%")
                    ->limit(50)
                    ->pluck('name', 'id')
                    ->all())
                ->getOptionLabelUsing(fn ($value): ?string => User::query()->find($value)?->name)
                ->default(fn (): ?int => auth()->id())
                ->columnSpan(1);

            if (Schema::hasTable('projects_task_categories')) {
                $schema[] = Select::make('category_id')
                    ->label(__('tasks.fields.category'))
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => TaskCategory::query()
                        ->where('is_active', true)
                        ->where('name', 'like', "%{$search}%")
                        ->limit(50)
                        ->pluck('name', 'id')
                        ->all())
                    ->getOptionLabelUsing(fn ($value): ?string => TaskCategory::query()->find($value)?->name)
                    ->columnSpan(1);
            }

            if (Schema::hasTable('employees_departments')) {
                $schema[] = Select::make('department_id')
                    ->label(__('tasks.fields.department'))
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => Department::query()
                        ->where('name', 'like', "%{$search}%")
                        ->limit(50)
                        ->pluck('name', 'id')
                        ->all())
                    ->getOptionLabelUsing(fn ($value): ?string => Department::query()->find($value)?->name)
                    ->columnSpan(1);
            }

            $schema[] = DateTimePicker::make('start_date')
                ->label(__('tasks.fields.start_date'))
                ->native(false)
                ->suffixIcon('heroicon-o-calendar')
                ->columnSpan(1);
        }

        $schema[] = ToggleButtons::make('state')
            ->label(__('projects::filament/resources/task.table.columns.state'))
            ->options(self::stateOptions())
            ->default(TaskStatePresenter::defaultState()->value)
            ->inline()
            ->columnSpanFull();

        return $schema;
    }

    /** @return array<string, string> */
    public static function stateOptions(): array
    {
        return TaskStatePresenter::options();
    }

    public static function defaultState(): TaskState
    {
        return TaskStatePresenter::defaultState();
    }

    public static function applyTableEagerLoads(Builder $query): Builder
    {
        $with = [
            'users.employee',
            'project',
            'milestone',
            'partner',
            'stage',
            'tags',
        ];

        if (Schema::hasColumn('projects_tasks', 'owner_id')) {
            $with[] = 'owner';
        }

        if (Schema::hasTable('projects_task_categories')) {
            $with[] = 'category';
        }

        if (Schema::hasTable('employees_departments')) {
            $with[] = 'department';
        }

        return $query->with($with);
    }

    /** @return array<int, TextColumn> */
    public static function extraTableColumns(): array
    {
        $columns = [];

        if (! Schema::hasTable('employees_employees')) {
            return $columns;
        }

        $columns[] = TextColumn::make('assignee_employees')
            ->label(__('projects-extensions::columns.manager'))
            ->state(function ($record): string {
                if (! $record->relationLoaded('users')) {
                    $record->load('users.employee');
                }

                $names = $record->users
                    ->map(fn (User $user): ?string => $user->employee?->name ?? $user->name)
                    ->filter()
                    ->values()
                    ->all();

                return $names !== [] ? implode(', ', $names) : '—';
            })
            ->toggleable(isToggledHiddenByDefault: true);

        if (! self::extendedFieldsAvailable()) {
            return $columns;
        }

        $columns[] = TextColumn::make('owner.name')
            ->label(__('tasks.columns.owner'))
            ->toggleable()
            ->placeholder('—');

        if (Schema::hasTable('projects_task_categories')) {
            $columns[] = TextColumn::make('category.name')
                ->label(__('tasks.columns.category'))
                ->badge()
                ->color('primary')
                ->toggleable()
                ->placeholder('—');
        }

        if (Schema::hasTable('employees_departments')) {
            $columns[] = TextColumn::make('department.name')
                ->label(__('tasks.columns.department'))
                ->toggleable()
                ->placeholder('—');
        }

        $columns[] = TextColumn::make('priority_level')
            ->label(__('tasks.columns.priority_level'))
            ->badge()
            ->formatStateUsing(fn (?string $state): string => TaskPriorityLevel::tryFrom((string) $state)?->getLabel() ?? '—')
            ->color(fn (?string $state): string => TaskPriorityLevel::tryFrom((string) $state)?->getColor() ?? 'gray')
            ->toggleable();

        $columns[] = TextColumn::make('start_date')
            ->label(__('tasks.columns.start_date'))
            ->date('d M Y')
            ->toggleable(isToggledHiddenByDefault: true);

        $columns[] = IconColumn::make('is_overdue')
            ->label(__('tasks.columns.overdue'))
            ->boolean()
            ->state(fn (Task $record): bool => TaskStatePresenter::isOverdue($record))
            ->trueIcon('heroicon-o-exclamation-triangle')
            ->falseIcon('heroicon-o-check-circle')
            ->trueColor('danger')
            ->falseColor('success')
            ->toggleable();

        return $columns;
    }

    public static function archiveAction(): Action
    {
        return Action::make('archiveTask')
            ->label(__('tasks.actions.archive'))
            ->icon('heroicon-o-archive-box')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading(__('tasks.actions.archive'))
            ->modalDescription(__('tasks.actions.archive_confirm'))
            ->action(function (Task $record): void {
                if (Schema::hasColumn('projects_tasks', 'completed_at')) {
                    $record->forceFill([
                        'state'        => TaskState::DONE,
                        'completed_at' => now(),
                    ])->save();
                }

                $record->delete();

                Notification::make()
                    ->success()
                    ->title(__('tasks.actions.archived'))
                    ->send();
            });
    }

    public static function prepareQuickCreateData(array $data): array
    {
        $data['state'] ??= TaskStatePresenter::defaultState()->value;
        $data['priority_level'] ??= TaskPriorityLevel::Medium->value;
        $data['owner_id'] ??= auth()->id();
        $data['stage_id'] ??= TaskStage::query()->orderBy('sort')->value('id');

        if (($data['priority_level'] ?? null) === TaskPriorityLevel::High->value) {
            $data['priority'] = true;
        }

        return $data;
    }
}
