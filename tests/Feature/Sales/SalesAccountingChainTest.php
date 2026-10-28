<?php

use App\Exceptions\PeriodLockedException;
use App\Support\Accounting\PeriodLock;
use Carbon\CarbonImmutable;
use Tests\Helpers\FeatureTestBootstrap;
use Tests\Helpers\SalesFixtures;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Facades\Account as AccountFacade;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap([
        'sales'       => 'sales_orders',
        'inventories' => 'inventories_operations',
    ]);

    $this->user = User::query()->firstOrFail();

    $this->actingAs($this->user);

    PeriodLock::forget();
});

afterEach(function (): void {
    PeriodLock::forget();
});

it('posts a balanced entry for a delivered order: receivable, sales and VAT', function (): void {
    [, $invoice] = SalesFixtures::deliveredAndInvoicedOrder();

    $invoice = AccountFacade::confirmMove($invoice)->refresh();

    $lines = $invoice->lines()->get();

    expect($invoice->state)->toBe(MoveState::POSTED)
        ->and((float) $invoice->amount_total)->toBe(210.0)
        ->and(round((float) $lines->sum('debit'), 3))->toBe(round((float) $lines->sum('credit'), 3))
        ->and(round((float) $lines->where('display_type.value', 'payment_term')->sum('debit'), 3))->toBe(210.0)
        ->and(round((float) $lines->where('display_type.value', 'tax')->sum('credit'), 3))->toBe(10.0)
        ->and(round((float) $lines->where('display_type.value', 'product')->sum('credit'), 3))->toBe(200.0);
});

it('numbers the posted invoice and links it to the order', function (): void {
    [$order, $invoice] = SalesFixtures::deliveredAndInvoicedOrder();

    $invoice = AccountFacade::confirmMove($invoice)->refresh();

    expect($invoice->name)->toMatch('#^INV/\d{4}/\d+$#')
        ->and($order->accountMoves()->pluck('accounts_account_moves.id')->all())->toContain($invoice->id)
        ->and($order->fresh()->invoice_status->value)->toBe('invoiced');
});

it('refuses to post the invoice of a delivered order into a locked period but still lets the delivery stand', function (): void {
    [$order, $invoice] = SalesFixtures::deliveredAndInvoicedOrder();

    PeriodLock::setLockDate($order->company_id, CarbonImmutable::today(), $this->user);
    PeriodLock::forget();

    expect(fn () => AccountFacade::confirmMove($invoice->fresh()))->toThrow(PeriodLockedException::class);

    expect($invoice->fresh()->state)->toBe(MoveState::DRAFT)
        ->and((float) $order->fresh()->lines->first()->qty_delivered)->toBe(20.0);
});
