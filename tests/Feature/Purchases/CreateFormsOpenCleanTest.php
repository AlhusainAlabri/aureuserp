<?php

use App\Filament\Purchases\Pages\CreatePurchaseOrder;
use App\Filament\Purchases\Pages\CreateQuotation;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap([
        'purchases' => 'purchases_orders',
    ]);
});

it('opens the purchase create forms without validation errors', function (): void {
    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));

    Gate::before(fn (): bool => true);
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    foreach ([CreateQuotation::class, CreatePurchaseOrder::class] as $page) {
        $component = Livewire::test($page);
        $component->assertSuccessful();
        $component->assertHasNoFormErrors();
    }
});
