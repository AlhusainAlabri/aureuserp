<?php

use App\Enums\Purchases\RequestType;
use App\Filament\Extensions\PurchaseOrderResourceExtensions;
use App\Mail\VendorPurchaseOrderMail;
use App\Services\Purchases\ExtendedPurchaseOrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Models\Order;
use Webkul\Purchase\Models\PurchaseOrder;
use Webkul\Security\Models\User;
use Wezlo\FilamentApproval\ApproverResolvers\UserResolver;
use Wezlo\FilamentApproval\Models\ApprovalFlow;
use Wezlo\FilamentApproval\Services\ApprovalEngine;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap([
        'purchases' => 'purchases_orders',
    ]);
});

function purchaseFixesTestUser(): User
{
    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    test()->actingAs($user);

    return $user;
}

function purchaseApprovalFlow(array $approverIds, int $steps = 1): ApprovalFlow
{
    $flow = ApprovalFlow::query()->create([
        'name'            => 'Purchase Test Flow',
        'approvable_type' => (new PurchaseOrder)->getMorphClass(),
        'is_active'       => true,
    ]);

    for ($step = 1; $step <= $steps; $step++) {
        $flow->steps()->create([
            'name'               => "Step {$step}",
            'order'              => $step,
            'type'               => 'single',
            'approver_resolver'  => UserResolver::class,
            'approver_config'    => ['user_ids' => $approverIds],
            'required_approvals' => 1,
        ]);
    }

    return $flow->fresh('steps');
}

it('runs full purchase approval flow', function (): void {
    $approver = purchaseFixesTestUser();
    $payload = [
        ...Order::factory()->make()->toArray(),
        'state' => OrderState::DRAFT,
    ];

    if (Schema::hasColumn('purchases_orders', 'request_type')) {
        $payload['request_type'] = RequestType::OfficeSupplies->value;
    }

    $order = PurchaseOrder::query()->create($payload);
    $flow = purchaseApprovalFlow([$approver->id], 2);
    $engine = app(ApprovalEngine::class);

    $approval = $order->submitForApproval($flow, $approver->id);

    expect($order->refresh()->isPendingApproval())->toBeTrue();

    $engine->approve($approval->currentStepInstance(), $approver->id);
    $approval->refresh();
    $engine->approve($approval->currentStepInstance(), $approver->id);

    expect($order->refresh()->isApproved())->toBeTrue();
});

it('blocks purchase state change until approval completes', function (): void {
    $approver = purchaseFixesTestUser();
    $payload = [
        ...Order::factory()->make()->toArray(),
        'state' => OrderState::DRAFT,
    ];

    if (Schema::hasColumn('purchases_orders', 'request_type')) {
        $payload['request_type'] = RequestType::DeviceRequest->value;
    }

    $order = PurchaseOrder::query()->create($payload);

    $order->submitForApproval(purchaseApprovalFlow([$approver->id], 1), $approver->id);

    expect(fn () => $order->update(['state' => OrderState::PURCHASE]))
        ->toThrow(RuntimeException::class);
});

it('stores generated purchase pdfs on the private disk', function (): void {
    Storage::fake('private');

    $order = Order::factory()->create();
    $service = app(ExtendedPurchaseOrderService::class);

    $path = $service->generatePurchaseOrderPdf($order);

    expect($path)->toStartWith('purchases/orders/')
        ->and(Storage::disk('private')->exists($path))->toBeTrue();
});

it('queues vendor purchase order mail', function (): void {
    Mail::fake();
    Storage::fake('private');

    $order = Order::factory()->create();
    $vendor = $order->partner;
    $vendor->update(['email' => 'vendor@example.com']);

    app(ExtendedPurchaseOrderService::class)->sendRFQ($order, [
        'vendors' => [$vendor->id],
        'subject' => 'Test RFQ',
        'message' => 'Please review the attached RFQ.',
    ]);

    Mail::assertQueued(VendorPurchaseOrderMail::class, fn (VendorPurchaseOrderMail $mail): bool => $mail->hasTo('vendor@example.com'));
});

it('implements should queue on vendor purchase order mail', function (): void {
    expect(new VendorPurchaseOrderMail('Subject', 'Body', 'purchases/rfqs/2026/test.pdf'))
        ->toBeInstanceOf(ShouldQueue::class);
});

it('formats purchase order payment amounts with locale aware omr prefix', function (): void {
    app()->setLocale('en');

    expect(PurchaseOrderResourceExtensions::formatOmrAmount(10.5))
        ->toBe('OMR 10.500');

    app()->setLocale('ar');

    expect(PurchaseOrderResourceExtensions::formatOmrAmount(10.5))
        ->toBe('ر.ع. 10.500');
});
