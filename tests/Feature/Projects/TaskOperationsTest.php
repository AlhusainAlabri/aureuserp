<?php

use App\Console\Commands\NotifyTaskDeadlines;
use App\Filament\Projects\Pages\OperationsCalendar;
use App\Filament\Projects\Pages\TaskKanban;
use App\Filament\Projects\Pages\TaskOperationsHub;
use App\Filament\Projects\Resources\TaskResource\Pages\ListTasks;
use App\Filament\Projects\Widgets\OperationsCalendarWidget;
use App\Filament\Projects\Widgets\TasksByStatusChartWidget;
use App\Services\Projects\TaskStatePresenter;
use App\Services\Projects\UnifiedTaskQueryService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Webkul\Project\Enums\TaskState;
use Webkul\Project\Filament\Resources\TaskResource;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Task;
use Webkul\Project\Models\TaskStage;
use Webkul\Security\Enums\PermissionType;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;

beforeEach(function (): void {
    static $booted = false;

    if (! $booted) {
        foreach ([1, 2, 3] as $index) {
            Currency::query()->firstOrCreate(
                ['iso_numeric' => sprintf('%03d', $index)],
                [
                    'name'           => "Test Currency {$index}",
                    'symbol'         => 'T',
                    'decimal_places' => 2,
                    'full_name'      => "Test Currency {$index}",
                    'rounding'       => 0.01,
                    'active'         => true,
                ],
            );
        }

        if (Company::query()->doesntExist()) {
            Artisan::call('erp:install', [
                '--force'          => true,
                '--no-interaction' => true,
                '--admin-name'     => 'Test Admin',
                '--admin-email'    => 'admin@test.com',
                '--admin-password' => 'password',
            ]);
        }

        foreach ([
            'page_task_operations_hub',
            'page_task_kanban',
            'page_operations_calendar',
            'view_any_project_task',
            'create_project_task',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $booted = true;
    }

    if (! Schema::hasTable('projects_tasks')) {
        test()->markTestSkipped('Projects plugin is not installed.');
    }
});

function taskOperationsUser(array $extraPermissions = []): User
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::withoutEvents(fn (): User => User::factory()->create([
        'resource_permission' => PermissionType::GLOBAL,
    ]));

    foreach ([
        'page_task_operations_hub',
        'page_task_kanban',
        'page_operations_calendar',
        'view_any_project_task',
        'create_project_task',
        ...$extraPermissions,
    ] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    test()->actingAs($user);

    return $user;
}

function taskOperationsSetup(): array
{
    $user = taskOperationsUser();

    $project = Project::query()->create([
        'name'       => 'Operations Project',
        'user_id'    => $user->id,
        'company_id' => $user->default_company_id,
    ]);

    $stage = TaskStage::query()->create([
        'name'       => 'To Do',
        'is_active'  => true,
        'project_id' => $project->id,
        'company_id' => $user->default_company_id,
        'user_id'    => $user->id,
        'creator_id' => $user->id,
    ]);

    return compact('user', 'project', 'stage');
}

it('uses unified pending label for approved state', function (): void {
    app()->setLocale('en');

    expect(TaskState::APPROVED->getLabel())->toBe('Pending')
        ->and(TaskState::CHANGE_REQUESTED->getLabel())->toBe('On Hold')
        ->and(TaskState::DONE->getLabel())->toBe('Completed');
});

it('uses unified pending label for approved state in arabic', function (): void {
    app()->setLocale('ar');

    expect(TaskState::APPROVED->getLabel())->toBe('قيد الانتظار');
});

it('defaults new tasks to pending state', function (): void {
    expect(TaskStatePresenter::defaultState())->toBe(TaskState::APPROVED);
});

it('stores extended task operation fields', function (): void {
    ['user' => $user, 'project' => $project, 'stage' => $stage] = taskOperationsSetup();

    $task = new Task;
    $task->forceFill([
        'title'          => 'Extended task',
        'state'          => TaskState::IN_PROGRESS,
        'stage_id'       => $stage->id,
        'project_id'     => $project->id,
        'company_id'     => $user->default_company_id,
        'creator_id'     => $user->id,
        'owner_id'       => $user->id,
        'priority_level' => 'urgent',
        'start_date'     => now()->subDay(),
        'deadline'       => now()->addDays(2),
    ]);
    $task->save();

    expect($task->fresh())
        ->priority_level->toBe('urgent')
        ->owner_id->toBe($user->id)
        ->start_date->not->toBeNull();
});

it('marks completed_at when task is done', function (): void {
    ['user' => $user, 'project' => $project, 'stage' => $stage] = taskOperationsSetup();

    $task = Task::query()->create([
        'title'      => 'Complete me',
        'state'      => TaskState::IN_PROGRESS,
        'stage_id'   => $stage->id,
        'project_id' => $project->id,
        'company_id' => $user->default_company_id,
        'creator_id' => $user->id,
    ]);

    $task->update(['state' => TaskState::DONE]);

    expect($task->fresh()->completed_at)->not->toBeNull();
});

it('loads the task operations hub', function (): void {
    taskOperationsUser();

    Livewire::test(TaskOperationsHub::class)
        ->assertSuccessful();
});

it('loads the task kanban board with stages', function (): void {
    taskOperationsSetup();

    Livewire::test(TaskKanban::class)
        ->assertSuccessful()
        ->assertSee('To Do');
});

it('seeds kanban demo stages and tasks', function (): void {
    taskOperationsUser();

    Artisan::call('projects:seed-kanban-demo', ['--force' => true]);

    expect(TaskStage::query()->where('is_active', true)->count())->toBeGreaterThanOrEqual(4)
        ->and(Task::query()->whereNull('parent_id')->count())->toBeGreaterThanOrEqual(8);

    Livewire::test(TaskKanban::class)
        ->assertSuccessful()
        ->assertSee('إعداد تقرير الربع الثاني');
});

it('returns project task events for the operations calendar', function (): void {
    ['user' => $user, 'project' => $project, 'stage' => $stage] = taskOperationsSetup();

    Task::query()->create([
        'title'      => 'Calendar task',
        'state'      => TaskState::IN_PROGRESS,
        'stage_id'   => $stage->id,
        'project_id' => $project->id,
        'company_id' => $user->default_company_id,
        'creator_id' => $user->id,
        'deadline'   => now(),
    ]);

    $events = app(OperationsCalendarWidget::class)->fetchEvents([
        'start' => now()->startOfMonth()->toIso8601String(),
        'end'   => now()->endOfMonth()->toIso8601String(),
    ]);

    expect(collect($events)->pluck('title'))->toContain('Calendar task');
});

it('counts overdue project tasks in unified query service', function (): void {
    ['user' => $user, 'project' => $project, 'stage' => $stage] = taskOperationsSetup();

    Task::query()->create([
        'title'      => 'Overdue task',
        'state'      => TaskState::IN_PROGRESS,
        'stage_id'   => $stage->id,
        'project_id' => $project->id,
        'company_id' => $user->default_company_id,
        'creator_id' => $user->id,
        'deadline'   => now()->subDay(),
    ]);

    expect(UnifiedTaskQueryService::countOverdueProjectTasks())->toBeGreaterThan(0);
});

it('runs the task deadline notification command', function (): void {
    taskOperationsSetup();

    Artisan::call(NotifyTaskDeadlines::class);

    expect(Artisan::output())->toContain('task reminder');
});

it('loads the operations calendar page', function (): void {
    taskOperationsUser();

    Livewire::test(OperationsCalendar::class)
        ->assertSuccessful();
});

it('registers the task hub under the project navigation group', function (): void {
    expect(TaskOperationsHub::getNavigationGroup())
        ->toBe(TaskResource::getNavigationGroup());
});

it('uses arabic task resource labels', function (): void {
    app()->setLocale('ar');

    expect(TaskResource::getModelLabel())->toBe('المهام')
        ->and(TaskResource::getPluralModelLabel())->toBe('المهام');
});

it('renders a placeholder chart when no open tasks exist', function (): void {
    taskOperationsUser();

    $widget = Livewire::test(TasksByStatusChartWidget::class)->instance();
    $method = new ReflectionMethod($widget, 'getData');
    $method->setAccessible(true);
    $data = $method->invoke($widget);

    expect($data['labels'])->toBe([__('tasks.empty.no_open_tasks_chart')])
        ->and($data['datasets'][0]['data'])->toBe([1]);
});

it('uses a half-width layout for task hub widgets', function (): void {
    expect((new TasksByStatusChartWidget)->getColumnSpan())
        ->toBe(['default' => 12, 'lg' => 6]);
});

it('shows arabic empty state on the task list', function (): void {
    taskOperationsUser();
    app()->setLocale('ar');

    Livewire::test(ListTasks::class)
        ->assertSuccessful()
        ->assertSee(__('tasks.empty.no_records'));
});
