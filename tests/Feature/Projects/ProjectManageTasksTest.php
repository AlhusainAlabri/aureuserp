<?php

use App\Filament\Resources\ProjectResource\Pages\ManageTasks;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Webkul\Project\Enums\TaskState;
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
                ]
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

        $booted = true;
    }

    if (! Schema::hasTable('projects_projects')) {
        test()->markTestSkipped('Projects plugin is not installed.');
    }
});

function projectManageTasksUser(array $permissions = []): User
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::withoutEvents(fn (): User => User::factory()->create([
        'resource_permission' => PermissionType::GLOBAL,
    ]));

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    test()->actingAs($user);

    return $user;
}

function projectManageTasksSetup(): array
{
    $user = projectManageTasksUser([
        'view_any_project_project',
        'view_project_project',
        'create_project_task',
        'view_any_project_task',
    ]);

    $project = Project::factory()->create([
        'company_id' => Company::query()->value('id'),
        'user_id'    => $user->id,
        'creator_id' => $user->id,
    ]);

    $stage = TaskStage::factory()->create([
        'project_id' => $project->id,
        'company_id' => $project->company_id,
        'user_id'    => $user->id,
        'creator_id' => $user->id,
    ]);

    return compact('user', 'project', 'stage');
}

function projectManageTasksCreateTask(Project $project, TaskStage $stage, User $user, array $attributes = []): Task
{
    return Task::query()->create([
        'title'       => 'Sample Task',
        'state'       => TaskState::IN_PROGRESS,
        'project_id'  => $project->id,
        'stage_id'    => $stage->id,
        'partner_id'  => $project->partner_id,
        'company_id'  => $project->company_id,
        'creator_id'  => $user->id,
        'is_active'   => true,
        'parent_id'   => null,
        ...$attributes,
    ]);
}

it('ProjectManageTasksTest: creates multiple tasks inline with project prefill', function (): void {
    ['user' => $user, 'project' => $project, 'stage' => $stage] = projectManageTasksSetup();

    test()->actingAs($user);

    $component = Livewire::test(ManageTasks::class, ['record' => $project->id]);

    foreach (['Task Alpha', 'Task Beta', 'Task Gamma'] as $title) {
        $component
            ->callAction(TestAction::make('create')->table(), [
                'title'    => $title,
                'stage_id' => $stage->id,
                'state'    => TaskState::IN_PROGRESS,
            ])
            ->assertHasNoFormErrors()
            ->assertNotified();
    }

    $tasks = Task::query()
        ->where('project_id', $project->id)
        ->whereNull('parent_id')
        ->whereIn('title', ['Task Alpha', 'Task Beta', 'Task Gamma'])
        ->get();

    expect($tasks)->toHaveCount(3)
        ->and($tasks->every(fn (Task $task): bool => $task->project_id === $project->id))->toBeTrue();
});

it('ProjectManageTasksTest: preset tabs filter open and closed tasks', function (): void {
    ['user' => $user, 'project' => $project, 'stage' => $stage] = projectManageTasksSetup();

    $openTask = projectManageTasksCreateTask($project, $stage, $user, [
        'state' => TaskState::IN_PROGRESS,
        'title' => 'Open Task For Filter',
    ]);

    $closedTask = projectManageTasksCreateTask($project, $stage, $user, [
        'state' => TaskState::DONE,
        'title' => 'Closed Task For Filter',
    ]);

    $myTask = projectManageTasksCreateTask($project, $stage, $user, [
        'state' => TaskState::IN_PROGRESS,
        'title' => 'My Assigned Task',
    ]);
    $myTask->users()->attach($user->id);

    test()->actingAs($user);

    Livewire::test(ManageTasks::class, ['record' => $project->id])
        ->loadTable()
        ->assertCanSeeTableRecords([$openTask, $myTask])
        ->assertCanNotSeeTableRecords([$closedTask])
        ->set('activeTableView', 'closed_tasks')
        ->assertCanSeeTableRecords([$closedTask])
        ->assertCanNotSeeTableRecords([$openTask])
        ->set('activeTableView', 'my_tasks')
        ->assertCanSeeTableRecords([$myTask])
        ->assertCanNotSeeTableRecords([$closedTask]);
});

it('ProjectManageTasksTest: creates a task when the project has no task stages yet', function (): void {
    ['user' => $user, 'project' => $project] = projectManageTasksSetup();

    $project->taskStages()->delete();
    TaskStage::query()->where('project_id', $project->id)->forceDelete();

    test()->actingAs($user);

    Livewire::test(ManageTasks::class, ['record' => $project->id])
        ->callAction(TestAction::make('create')->table(), [
            'title' => 'Auto Stage Task',
            'state' => TaskState::IN_PROGRESS,
        ])
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect(TaskStage::query()->where('project_id', $project->id)->count())->toBe(1)
        ->and(Task::query()->where('project_id', $project->id)->where('title', 'Auto Stage Task')->exists())->toBeTrue();
});

it('ProjectManageTasksTest: add task action label is translated in Arabic', function (): void {
    app()->setLocale('ar');

    expect(__('projects-extensions::actions.add_task'))->toBe('إضافة مهمة');
});
