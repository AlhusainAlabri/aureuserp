<?php

use Webkul\Account\Database\Factories\MoveLineFactory;
use Webkul\Account\Enums\AccountType;
use Webkul\Account\Enums\DisplayType;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Enums\MoveType;
use Webkul\Account\Enums\PaymentState;
use Webkul\Account\Facades\Account as AccountFacade;
use Webkul\Account\Models\Account;
use Webkul\Account\Models\Move;
use Webkul\Account\Models\PartialReconcile;
use Webkul\Account\Models\Tax;
use Webkul\Security\Models\User;

it('keeps line taxes and starts as a draft when reversing an invoice', function (): void {
    $this->actingAs(User::factory()->create());

    $move = Move::factory()->invoice()->create(['state' => MoveState::POSTED]);
    $line = MoveLineFactory::new()->create(['move_id' => $move->id]);
    $tax = Tax::factory()->create();
    $line->taxes()->sync([$tax->id]);

    $reversed = AccountFacade::reverseMoves(collect([$move->fresh()]), ['date' => '2026-03-05', 'reference' => 'Reversal of: test'])->first();

    expect($reversed->state)->toBe(MoveState::DRAFT)
        ->and($reversed->date->toDateString())->toBe('2026-03-05')
        ->and($reversed->reference)->toBe('Reversal of: test')
        ->and($reversed->payment_state)->toBe(PaymentState::NOT_PAID)
        ->and($reversed->lines->flatMap->taxes->pluck('id')->all())->toContain($tax->id);
});

it('keeps aggregated reconcile rows from the debit side when computing the payment state', function (): void {
    $this->actingAs(User::factory()->create());

    $move = Move::factory()->invoice()->create([
        'state'           => MoveState::POSTED,
        'amount_total'    => 100,
        'amount_residual' => 60,
    ]);
    $receivable = MoveLineFactory::new()->create([
        'move_id'    => $move->id,
        'account_id' => Account::factory()->create(['account_type' => AccountType::ASSET_RECEIVABLE])->id,
    ]);
    $payment = MoveLineFactory::new()->create();

    PartialReconcile::query()->insert([
        'debit_move_id'  => $receivable->id,
        'credit_move_id' => $payment->id,
        'amount'         => 1,
        'company_id'     => $move->company_id,
        'max_date'       => now()->toDateString(),
    ]);

    $move->computePaymentState();

    expect($move->payment_state)->not->toBe(PaymentState::NOT_PAID);
});

it('keeps the receivable line and its reconciliations when a posted invoice without payment term is recomputed', function (): void {
    $this->actingAs(User::factory()->create());

    $move = Move::factory()->invoice()->create([
        'state'                   => MoveState::POSTED,
        'invoice_payment_term_id' => null,
        'invoice_date_due'        => now()->toDateString(),
    ]);
    MoveLineFactory::new()->create([
        'move_id'      => $move->id,
        'display_type' => DisplayType::PRODUCT,
        'price_unit'   => 100,
        'quantity'     => 1,
    ]);

    AccountFacade::computeAccountMove($move->fresh());

    $termLineId = $move->fresh()->paymentTermLines->first()->id;

    AccountFacade::computeAccountMove($move->fresh());

    expect($move->fresh()->paymentTermLines->pluck('id')->all())->toBe([$termLineId]);
});

it('refuses to post an unbalanced journal entry and accepts a balanced one', function (): void {
    $this->actingAs(User::factory()->create());

    $entry = Move::factory()->create(['state' => MoveState::DRAFT, 'move_type' => MoveType::ENTRY]);
    $account = Account::factory()->create(['deprecated' => false]);

    MoveLineFactory::new()->create(['move_id' => $entry->id, 'account_id' => $account->id, 'debit' => 50, 'credit' => 0]);
    MoveLineFactory::new()->create(['move_id' => $entry->id, 'account_id' => $account->id, 'debit' => 0, 'credit' => 40]);

    $record = $entry->fresh();

    expect(fn () => AccountFacade::isConfirmAllowedForMove($record))
        ->toThrow(Exception::class, __('accounts::account-manager.post-action-validate.unbalanced'));

    MoveLineFactory::new()->create(['move_id' => $entry->id, 'account_id' => $account->id, 'debit' => 0, 'credit' => 10]);

    $record = $entry->fresh();

    expect(fn () => AccountFacade::isConfirmAllowedForMove($record))
        ->not->toThrow(Exception::class, __('accounts::account-manager.post-action-validate.unbalanced'));
});
