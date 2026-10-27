<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\TeamResource\Pages\CreateTeam;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\CreateQuotation;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\EditQuotation;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\ListQuotations;
use Webkul\Sale\Models\Order;
use Webkul\Sale\Models\Team;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap([
        'sales' => 'sales_orders',
    ]);

    Gate::before(fn (): bool => true);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->user = User::query()->firstOrFail();

    $this->actingAs($this->user);
});

it('does not offer your own company or user accounts as customers', function (): void {
    $companyPartnerId = Company::query()->whereNotNull('partner_id')->value('partner_id');
    $userPartnerId = User::query()->whereNotNull('partner_id')->value('partner_id');

    expect($companyPartnerId ?? $userPartnerId)->not->toBeNull();

    $component = Livewire::test(CreateQuotation::class);

    $options = $component->instance()->form->getFlatFields()['partner_id']->getSearchResults('');

    expect(array_keys($options))
        ->not->toContain($companyPartnerId)
        ->not->toContain($userPartnerId);
});

it('shows the expiry date on a draft quotation but not on a confirmed order', function (): void {
    $draft = Order::factory()->create(['state' => OrderState::DRAFT]);
    $confirmed = Order::factory()->create(['state' => OrderState::SALE]);

    Livewire::test(EditQuotation::class, ['record' => $draft->getKey()])
        ->assertFormFieldIsVisible('validity_date');

    Livewire::test(EditQuotation::class, ['record' => $confirmed->getKey()])
        ->assertFormFieldIsHidden('validity_date');
});

it('keeps confirmed orders out of the default my quotations tab', function (): void {
    $open = Order::factory()->create(['state' => OrderState::DRAFT, 'user_id' => $this->user->id]);
    $confirmed = Order::factory()->create(['state' => OrderState::SALE, 'user_id' => $this->user->id]);

    Livewire::test(ListQuotations::class)
        ->assertCanSeeTableRecords([$open])
        ->assertCanNotSeeTableRecords([$confirmed]);
});

it('shows a single total in the quotation list footer', function (): void {
    $table = Livewire::test(ListQuotations::class)->instance()->getTable();

    expect($table->getColumn('amount_total')->getSummarizers())->toHaveCount(1);
});

it('defaults the salesperson to the signed-in user and offers a sales team', function (): void {
    Livewire::test(CreateQuotation::class)
        ->assertFormSet(['user_id' => $this->user->id])
        ->assertFormFieldExists('team_id');
});

it('creates a sales team that belongs to the signed-in user\'s company by default', function (): void {
    Livewire::test(CreateTeam::class)
        ->assertFormSet(['company_id' => $this->user->default_company_id])
        ->fillForm(['name' => 'فريق مبيعات مسقط'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Team::query()->where('name', 'فريق مبيعات مسقط')->value('company_id'))
        ->toBe($this->user->default_company_id);
});
