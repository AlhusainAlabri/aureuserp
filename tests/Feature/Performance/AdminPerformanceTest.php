<?php

use App\Filament\Pages\Dashboard;
use App\Filament\Projects\Resources\TaskResource\Pages\ListTasks;
use App\Filament\Widgets\Dashboard\LowStockWidget;
use App\Filament\Widgets\Dashboard\OrgDashboardCommandCenterWidget;
use App\Filament\Widgets\Dashboard\PendingApprovalsWidget;
use App\Support\Inventory\InventoryStockCounter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Webkul\Inventory\Models\OrderPoint;
use Webkul\Inventory\Models\Product;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Project\Enums\TaskState;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Task;
use Webkul\Project\Models\TaskStage;
use Webkul\Security\Enums\PermissionType;
use Webkul\Security\Models\User;

function performanceUserWithRole(string $roleName): User
{
    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
    $user->assignRole($role);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    test()->actingAs($user);

    return $user;
}

function performanceTaskUser(): User
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::withoutEvents(fn (): User => User::factory()->create([
        'resource_permission' => PermissionType::GLOBAL,
    ]));

    foreach (['view_any_project_task', 'create_project_task'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    test()->actingAs($user);

    return $user;
}

/**
 * @return array{user: User, project: Project, stage: TaskStage}
 */
function performanceTaskSetup(): array
{
    $user = performanceTaskUser();

    $project = Project::query()->create([
        'name'       => 'Performance Project',
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

it('records baseline query counts for dashboard and task list', function (): void {
    performanceUserWithRole('super_admin');

    DB::enableQueryLog();
    DB::flushQueryLog();

    Livewire::test(Dashboard::class)->assertSuccessful();
    $dashboardQueries = count(DB::getQueryLog());

    performanceTaskUser();

    DB::flushQueryLog();
    Livewire::test(ListTasks::class)->assertSuccessful();
    $taskListQueries = count(DB::getQueryLog());

    expect($dashboardQueries)->toBeLessThan(250)
        ->and($taskListQueries)->toBeLessThan(250);
});

function widgetIsLazy(string $widgetClass): bool
{
    $reflection = new ReflectionClass($widgetClass);
    $property = $reflection->getProperty('isLazy');
    $property->setAccessible(true);

    return (bool) $property->getValue();
}

it('uses cached inventory stock counts without loading all order points per call', function (): void {
    if (! Schema::hasTable('inventories_order_points')) {
        $this->markTestSkipped('Inventory not installed.');
    }

    performanceUserWithRole('super_admin');

    $warehouse = Warehouse::query()->first();

    if (! $warehouse) {
        $this->markTestSkipped('No warehouse seeded.');
    }

    $product = Product::factory()->create(['is_storable' => true]);

    OrderPoint::factory()->create([
        'product_id'      => $product->id,
        'warehouse_id'    => $warehouse->id,
        'location_id'     => $warehouse->lot_stock_location_id,
        'product_min_qty' => 10,
        'product_max_qty' => 50,
        'qty_multiple'    => 1,
    ]);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $first = InventoryStockCounter::counts();
    $firstQueryCount = count(DB::getQueryLog());

    DB::flushQueryLog();

    $second = InventoryStockCounter::counts();
    $secondQueryCount = count(DB::getQueryLog());

    expect($first)->toHaveKeys(['below_minimum', 'out_of_stock'])
        ->and($second)->toBe($first)
        ->and($secondQueryCount)->toBe(0);
});

it('defers non-command-center dashboard widgets with lazy loading', function (): void {
    expect(widgetIsLazy(PendingApprovalsWidget::class))->toBeTrue()
        ->and(widgetIsLazy(LowStockWidget::class))->toBeTrue()
        ->and(widgetIsLazy(OrgDashboardCommandCenterWidget::class))->toBeFalse();
});

it('eager loads task relationships on the task list without per-row employee queries', function (): void {
    if (! Schema::hasTable('projects_tasks')) {
        $this->markTestSkipped('Projects not installed.');
    }

    ['user' => $user, 'project' => $project, 'stage' => $stage] = performanceTaskSetup();

    Task::query()->create([
        'title'      => 'Performance task',
        'state'      => TaskState::IN_PROGRESS,
        'stage_id'   => $stage->id,
        'project_id' => $project->id,
        'company_id' => $user->default_company_id,
        'creator_id' => $user->id,
    ]);

    DB::enableQueryLog();
    DB::flushQueryLog();

    Livewire::test(ListTasks::class)->assertSuccessful();

    $employeeLookupQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains(strtolower($query['query']), 'from `employees_employees`')
            && str_contains(strtolower($query['query']), 'user_id'))
        ->count();

    expect($employeeLookupQueries)->toBeLessThanOrEqual(1);
});

it('renders low stock widget using inventory stock counter', function (): void {
    performanceUserWithRole('super_admin');

    Livewire::test(LowStockWidget::class)->assertSuccessful();
});
