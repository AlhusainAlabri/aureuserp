<?php

use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Filament\Resources\InvoiceResource\Actions\ResetToDraftAction;
use Webkul\Account\Models\Move;
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
