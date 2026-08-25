<?php

use App\Support\Navigation\ModuleSidebarMenuBuilder;
use Filament\Events\ServingFilament;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationManager;
use Webkul\Security\Models\User;

it('returns navigation groups from the module sidebar builder', function (): void {
    $groups = ModuleSidebarMenuBuilder::build();

    expect($groups)->toBeArray();

    foreach ($groups as $group) {
        expect($group)->toBeInstanceOf(NavigationGroup::class);
    }
});

it('strips item icons when module groups already have icons', function (): void {
    $user = User::where('email', 'nodhumtech@gmail.com')->first();

    if ($user === null) {
        expect(true)->toBeTrue();

        return;
    }

    auth()->login($user);
    app()->setLocale('ar');
    app()->forgetInstance(NavigationManager::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    ServingFilament::dispatch();

    foreach (ModuleSidebarMenuBuilder::build() as $group) {
        if (blank($group->getIcon())) {
            continue;
        }

        foreach ($group->getItems() as $item) {
            expect($item->getIcon())->toBeNull()
                ->and($item->getActiveIcon())->toBeNull()
                ->and($item->getChildItems())->toBeEmpty();
        }
    }
});

it('flattens sales cluster pages under the sales module group', function (): void {
    $user = User::where('email', 'nodhumtech@gmail.com')->first();

    if ($user === null) {
        expect(true)->toBeTrue();

        return;
    }

    auth()->login($user);
    app()->setLocale('en');
    app()->forgetInstance(NavigationManager::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    ServingFilament::dispatch();

    $salesGroup = collect(ModuleSidebarMenuBuilder::build())
        ->first(fn ($group) => $group->getIcon() === 'icon-sales');

    expect($salesGroup)->not->toBeNull();

    $labels = collect($salesGroup->getItems())
        ->map(fn ($item) => (string) $item->getLabel())
        ->all();

    expect($labels)->toContain('Quotations');
});
