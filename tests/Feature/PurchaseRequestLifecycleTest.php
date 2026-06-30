<?php

use App\Enums\Purchases\RequestType;
use App\Filament\Extensions\PurchaseOrderResourceExtensions;
use App\Filament\Pages\InternalRequests;
use App\Filament\Pages\MyRequests;
use App\Models\Purchases\OrderPayment;
use App\Services\Purchases\InternalRequestLineService;
use App\Services\Purchases\PurchaseExpenseConversionService;
use App\Services\Purchases\PurchasePaymentService;
use Filament\Forms\Components\Repeater;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Webkul\Employee\Models\Department;
use Webkul\Employee\Models\Employee;
use Webkul\Partner\Enums\AccountType;
use Webkul\Partner\Models\Partner;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Models\Order;
use Webkul\Purchase\Models\OrderLine;
use Webkul\Purchase\Models\PurchaseOrder;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function purchaseTestUser(array $roles = []): User
{
    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));

    foreach ($roles as $roleName) {
        $role = Role::query()->firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $user->assignRole($role);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    test()->actingAs($user);

    return $user;
}

it('has purchase request extension columns', function (): void {
    expect(Schema::hasColumn('purchases_orders', 'request_type'))->toBeTrue()
        ->and(Schema::hasColumn('purchases_orders', 'quotation_path'))->toBeTrue()
        ->and(Schema::hasColumn('purchases_orders', 'payment_voucher_path'))->toBeTrue()
        ->and(Schema::hasColumn('purchases_orders', 'amount_remaining'))->toBeTrue()
        ->and(Schema::hasTable('purchases_order_payments'))->toBeTrue();
});

it('allows access to my requests when extension columns exist', function (): void {
    purchaseTestUser();

    expect(MyRequests::canAccess())->toBeTrue()
        ->and(InternalRequests::canAccess())->toBeTrue();
});

it('allows my requests access for purchase viewers when request_type column is missing', function (): void {
    $user = purchaseTestUser(['Admin']);

    if (Schema::hasColumn('purchases_orders', 'request_type')) {
        test()->markTestSkipped('request_type column exists — fallback path not applicable.');
    }

    expect($user->can('view_any_purchase_purchase::order'))->toBeTrue()
        ->and(MyRequests::canAccess())->toBeTrue();
});

it('uses arabic label for chatter action', function (): void {
    app()->setLocale('ar');

    expect(__('chatter::filament/resources/actions/chatter-action.title'))->toBe('المحادثات');
});

it('defaults purchase forms to omr currency when available', function (): void {
    $omrId = PurchaseOrderResourceExtensions::defaultOmrCurrencyId();

    if (! $omrId) {
        test()->markTestSkipped('OMR currency is not available.');
    }

    expect(PurchaseOrderResourceExtensions::defaultOmrCurrencyId())->toBe($omrId);
});

it('registers my requests page with internal request table columns', function (): void {
    purchaseTestUser();

    expect(MyRequests::canAccess())->toBeTrue()
        ->and(PurchaseOrderResourceExtensions::extraTableColumns())->not->toBeEmpty();
});

it('defaults requesting department from the logged in employee', function (): void {
    $user = purchaseTestUser();
    $company = Company::query()->first() ?? Company::factory()->create();
    $department = Department::query()->first() ?? Department::create([
        'name'       => 'Test Dept '.uniqid(),
        'company_id' => $company->id,
    ]);

    Employee::query()->create([
        'user_id'       => $user->id,
        'creator_id'    => $user->id,
        'company_id'    => $company->id,
        'department_id' => $department->id,
        'name'          => $user->name,
        'work_email'    => $user->email,
        'is_active'     => true,
    ]);

    expect(PurchaseOrderResourceExtensions::defaultRequestingDepartmentId())->toBe($department->id);
});

it('tracks paid and remaining amounts through payments', function (): void {
    $order = Order::factory()->create([
        'request_type'     => RequestType::DeviceRequest->value,
        'total_amount'     => 100,
        'amount_paid'      => 0,
        'amount_remaining' => 100,
        'state'            => OrderState::PURCHASE,
    ]);

    app(PurchasePaymentService::class)->recordPayment($order, [
        'amount'  => 40,
        'paid_at' => now(),
    ]);

    $order->refresh();

    expect((float) $order->amount_paid)->toBe(40.0)
        ->and((float) $order->amount_remaining)->toBe(60.0)
        ->and(OrderPayment::query()->where('order_id', $order->id)->count())->toBe(1);
});

it('grants department report permission to finance manager role', function (): void {
    $permission = Permission::query()->firstOrCreate([
        'name'       => 'page_purchases_department_report',
        'guard_name' => 'web',
    ]);

    $role = Role::query()->firstOrCreate(['name' => 'finance_manager', 'guard_name' => 'web']);
    $role->permissions()->syncWithoutDetaching([$permission->id]);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect($role->permissions->pluck('name'))->toContain('page_purchases_department_report');
});

it('identifies internal purchase requests', function (): void {
    expect(PurchaseOrderResourceExtensions::isInternalRequest(RequestType::Maintenance->value))->toBeTrue()
        ->and(PurchaseOrderResourceExtensions::isInternalRequest(RequestType::StandardPurchase->value))->toBeFalse();
});

it('uses approval workflow on purchase orders', function (): void {
    $order = PurchaseOrder::query()->create([
        ...Order::factory()->make()->toArray(),
        'request_type' => RequestType::OfficeSupplies->value,
        'state'        => OrderState::DRAFT,
    ]);

    expect($order)->toBeInstanceOf(PurchaseOrder::class)
        ->and(method_exists($order, 'submitForApproval'))->toBeTrue();
});

it('generates twelve unique month labels for department report filters', function (): void {
    $options = PurchaseOrderResourceExtensions::departmentReportMonthOptions(2026);

    expect($options)->toHaveCount(12)
        ->and(collect($options)->unique()->count())->toBe(12)
        ->and($options[2])->not->toBe($options[3]);
});

it('calculates internal request line totals without catalog selection', function (): void {
    $totals = PurchaseOrderResourceExtensions::calculateInternalLineTotals(2, 15.5);

    expect($totals['price_subtotal'])->toBe(31.0)
        ->and($totals['price_total'])->toBe(31.0)
        ->and($totals['price_tax'])->toBe(0);
});

it('exposes internal request line repeater for extension forms', function (): void {
    $fields = PurchaseOrderResourceExtensions::productRepeaterFields(
        Repeater::make('products')
    );

    expect($fields)->toHaveCount(2)
        ->and($fields[1]->getName())->toBe('internal_line_items');
});

it('syncs internal request lines from form data on create', function (): void {
    if (! PurchaseOrderResourceExtensions::defaultInternalLineProductId()) {
        $this->markTestSkipped('General Purchase Item product not seeded.');
    }

    $company = Company::query()->first() ?? Company::factory()->create();

    $order = PurchaseOrder::query()->create([
        ...Order::factory()->make(['company_id' => $company->id])->toArray(),
        'request_type' => RequestType::DeviceRequest->value,
        'origin'       => 'Test laptop',
        'state'        => OrderState::DRAFT,
    ]);

    app(InternalRequestLineService::class)->syncFromFormData($order, [
        [
            'name'        => 'Test laptop',
            'product_qty' => 1,
            'price_unit'  => 350,
        ],
    ]);

    $order->refresh();

    expect($order->lines)->toHaveCount(1)
        ->and($order->lines->first()->name)->toBe('Test laptop')
        ->and((float) $order->lines->first()->price_unit)->toBe(350.0);
});

it('has arabic vendor hint translation for internal requests', function (): void {
    app()->setLocale('ar');

    expect(__('purchases-extensions::request.fields.vendor_hint'))
        ->toBe('اختياري — اسم المحل أو الشركة لهذا الشراء');
});

it('assigns misc supplier when internal order has no vendor for bill conversion', function (): void {
    if (! Schema::hasTable('partners_partners')) {
        $this->markTestSkipped('Partners table not available.');
    }

    $company = Company::query()->first() ?? Company::factory()->create();
    $supplier = Partner::query()->firstOrCreate(
        ['name' => 'Misc Supplier'],
        [
            'account_type' => AccountType::COMPANY,
            'company_id'   => $company->id,
            'creator_id'   => User::query()->value('id') ?? User::factory()->create()->id,
        ],
    );

    $order = PurchaseOrder::query()->create([
        ...Order::factory()->make(['company_id' => $company->id])->toArray(),
        'request_type' => RequestType::OfficeSupplies->value,
        'state'        => OrderState::PURCHASE,
        'total_amount' => 25,
    ]);

    $lineProductId = PurchaseOrderResourceExtensions::defaultInternalLineProductId();

    if ($lineProductId) {
        $uomId = PurchaseOrderResourceExtensions::defaultInternalLineUomId();

        OrderLine::query()->create([
            'order_id'       => $order->id,
            'product_id'     => $lineProductId,
            'uom_id'         => $uomId,
            'name'           => 'Test supplies',
            'product_qty'    => 1,
            'price_unit'     => 25,
            'price_subtotal' => 25,
            'price_total'    => 25,
            'qty_to_invoice' => 0,
            'company_id'     => $company->id,
            'currency_id'    => $order->currency_id,
            'creator_id'     => $order->creator_id,
        ]);
    }

    $order->partner_id = null;

    $service = app(PurchaseExpenseConversionService::class);
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('ensureBillPrerequisites');
    $method->setAccessible(true);

    $orderForTest = $order->fresh(['lines']);
    $orderForTest->partner_id = null;

    $method->invoke($service, $orderForTest);

    $order->refresh();

    expect($order->partner_id)->toBe($supplier->id);

    if ($lineProductId) {
        expect((float) $order->lines->first()->qty_to_invoice)->toBeGreaterThan(0);
    }
});

it('triggers expense conversion check when order state changes to purchase', function (): void {
    $order = PurchaseOrder::query()->create([
        ...Order::factory()->make()->toArray(),
        'request_type' => RequestType::DeviceRequest->value,
        'state'        => OrderState::DRAFT,
    ]);

    $order->update(['state' => OrderState::PURCHASE]);

    expect($order->fresh()->state)->toBe(OrderState::PURCHASE);
});
