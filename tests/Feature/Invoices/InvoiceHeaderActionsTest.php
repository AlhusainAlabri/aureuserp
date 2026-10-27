<?php

use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Filament\Resources\InvoiceResource\Actions\ResetToDraftAction;
use Webkul\Account\Models\Move;
use Webkul\Invoice\Filament\Clusters\Customers\Resources\CreditNoteResource\Pages\ViewCreditNote;
use Webkul\Invoice\Filament\Clusters\Customers\Resources\InvoiceResource\Pages\ViewInvoice;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap([
        'invoices' => 'accounts_account_moves',
    ]);

    Gate::before(fn (): bool => true);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs(User::query()->firstOrFail());
});

it('keeps the posted invoice header short by grouping the secondary actions under more actions', function (): void {
    $invoice = Move::factory()->invoice()->create(['state' => MoveState::POSTED]);

    $actions = collect(Livewire::test(ViewInvoice::class, ['record' => $invoice->id])
        ->instance()
        ->getCachedHeaderActions());

    $group = $actions->first(fn ($action) => $action instanceof ActionGroup);

    expect($group)->not->toBeNull()
        ->and($actions->reject(fn ($action) => $action instanceof ActionGroup)->filter->isVisible()->count())->toBeLessThanOrEqual(6);

    $grouped = collect($group->getActions())->map(fn ($action) => $action->getName())->all();

    expect($grouped)->toContain(ResetToDraftAction::getDefaultName());
});

it('opens a posted credit note and a draft one without the invoice-only actions', function (): void {
    foreach ([MoveState::POSTED, MoveState::DRAFT] as $state) {
        $creditNote = Move::factory()->create(['move_type' => 'out_refund', 'state' => $state]);

        $component = Livewire::test(ViewCreditNote::class, ['record' => $creditNote->id])
            ->assertSuccessful();

        $names = collect($component->instance()->getCachedHeaderActions())
            ->flatMap(fn ($action) => $action instanceof ActionGroup ? $action->getActions() : [$action])
            ->map(fn ($action) => $action->getName())
            ->all();

        expect($names)->not->toContain('customers.invoice.set-as-checked')
            ->and($names)->not->toContain('customers.invoice.credit-note');
    }
});
