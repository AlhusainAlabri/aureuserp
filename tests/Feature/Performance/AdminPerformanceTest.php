<?php

use App\Filament\Pages\Dashboard;
use App\Filament\Projects\Resources\TaskResource\Pages\ListTasks;
use App\Filament\Widgets\Dashboard\ActiveProjectsWidget;
use App\Filament\Widgets\Dashboard\LowStockWidget;
use App\Filament\Widgets\Dashboard\OrgDashboardCommandCenterWidget;
use App\Filament\Widgets\Dashboard\PendingApprovalsWidget;
use App\Support\Inventory\InventoryStockCounter;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema as FilamentSchema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Webkul\DocumentArchive\Filament\Widgets\DocumentStatsWidget;
use Webkul\Inventory\Models\OrderPoint;
use Webkul\Inventory\Models\Product;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Product\Settings\ProductSettings;
use Webkul\Project\Enums\TaskState;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Task;
use Webkul\Project\Models\TaskStage;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\EditQuotation;
use Webkul\Sale\Models\Order;
use Webkul\Sale\Models\OrderLine;
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

function countSettingsGroupQueries(string $group): int
{
    return collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains(strtolower($query['query']), 'from `settings`')
            && str_contains($query['query'], "'{$group}'"))
        ->count();
}

it('loads registered settings once per request when resolved repeatedly', function (): void {
    if (! class_exists(ProductSettings::class) || ! Schema::hasTable('settings')) {
        $this->markTestSkipped('Product settings unavailable.');
    }

    Artisan::call('settings:clear-cache');

    DB::enableQueryLog();
    DB::flushQueryLog();

    app(ProductSettings::class);
    app(ProductSettings::class);

    expect(countSettingsGroupQueries('products_product'))->toBeLessThanOrEqual(1);
});

it('disables preload on quotation repeater product selects in the resource form', function (): void {
    if (! class_exists(QuotationResource::class)) {
        $this->markTestSkipped('Sales plugin not installed.');
    }

    if (! Schema::hasTable('accounts_payment_terms')) {
        $this->markTestSkipped('Accounts plugin tables not available in test database.');
    }

    $this->markTestSkipped('Needs rework: the quotation form can only be built inside a Livewire component (see ledger 09).');

    performanceUserWithRole('super_admin');

    $schema = QuotationResource::form(FilamentSchema::make());
    $repeater = collect($schema->getFlatFields(withHidden: true))
        ->first(fn ($field) => $field instanceof Repeater && $field->getName() === 'products');

    expect($repeater)->not->toBeNull();

    $productSelect = collect($repeater->getDefaultChildComponents())
        ->first(fn ($field) => $field instanceof Select && $field->getName() === 'product_id');

    expect($productSelect)->toBeInstanceOf(Select::class)
        ->and($productSelect->isPreloaded())->toBeFalse();
});

it('keeps quotation edit livewire update query count bounded', function (): void {
    if (! Schema::hasTable('sales_orders') || ! class_exists(EditQuotation::class)) {
        $this->markTestSkipped('Sales not installed.');
    }

    if (! Schema::hasTable('accounts_payment_terms')) {
        $this->markTestSkipped('Accounts plugin tables not available in test database.');
    }

    $this->markTestSkipped('Needs rework: Livewire snapshot is rejected for this component in the test harness (see ledger 09).');

    performanceUserWithRole('super_admin');

    $order = Order::factory()->create();

    OrderLine::factory()->count(3)->create([
        'order_id' => $order->id,
    ]);

    DB::enableQueryLog();
    DB::flushQueryLog();

    Livewire::test(EditQuotation::class, ['record' => $order->id])
        ->set('data.origin', 'Performance test update')
        ->assertHasNoFormErrors();

    $settingsQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains(strtolower($query['query']), 'from `settings`'))
        ->count();

    expect($settingsQueries)->toBeLessThanOrEqual(25)
        ->and(count(DB::getQueryLog()))->toBeLessThan(200);
});

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

    expect($first)->toHaveKeys(['below_minimum', 'out_of_stock', 'total_skus'])
        ->and($second)->toBe($first)
        ->and($secondQueryCount)->toBe(0);
});

it('loads active projects widget without per-row completion queries', function (): void {
    if (! Schema::hasTable('projects_projects')) {
        $this->markTestSkipped('Projects not installed.');
    }

    ['user' => $user, 'project' => $project, 'stage' => $stage] = performanceTaskSetup();

    Task::query()->create([
        'title'      => 'Completion task',
        'state'      => TaskState::DONE,
        'stage_id'   => $stage->id,
        'project_id' => $project->id,
        'company_id' => $user->default_company_id,
        'creator_id' => $user->id,
    ]);

    DB::enableQueryLog();
    DB::flushQueryLog();

    Livewire::test(ActiveProjectsWidget::class)->assertSuccessful();

    $taskCountQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains(strtolower($query['query']), 'from `projects_tasks`')
            && str_contains(strtolower($query['query']), 'count'))
        ->count();

    expect($taskCountQueries)->toBeLessThanOrEqual(4);
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

it('keeps document stats widget boot overhead within query budget', function (): void {
    if (! Schema::hasTable('doc_files')) {
        $this->markTestSkipped('Document archive not installed.');
    }

    $user = performanceUserWithRole('super_admin');

    foreach ([
        'view_any_document_archive_doc::file',
        'view_document_archive_doc::file',
    ] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    DB::enableQueryLog();
    DB::flushQueryLog();

    Livewire::test(DocumentStatsWidget::class)
        ->assertSuccessful();

    expect(count(DB::getQueryLog()))->toBeLessThan(80);
});
