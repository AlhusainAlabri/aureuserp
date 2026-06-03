<?php

use App\Support\AdminNavigationMenu;
use Filament\Events\ServingFilament;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationManager;
use Webkul\Security\Models\User;

/**
 * @return array<int, array{label: string, icon: string, url: string}>
 */
function adminPrimaryMenuForLocale(string $locale): array
{
    app()->setLocale($locale);

    auth()->login(User::where('email', 'nodhumtech@gmail.com')->first());

    app()->forgetInstance(NavigationManager::class);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    ServingFilament::dispatch();

    return AdminNavigationMenu::primaryMenuItems(
        Filament::getNavigation(),
        Filament::getPanel('admin')->getNavigationGroups(),
    )
        ->map(fn (array $item): array => [
            'label' => $item['label'],
            'icon'  => $item['icon'],
            'url'   => $item['url'],
        ])
        ->values()
        ->all();
}

it('shows the same primary menu icons in english and arabic', function (): void {
    $englishIcons = collect(adminPrimaryMenuForLocale('en'))->pluck('icon')->values()->all();
    $arabicIcons = collect(adminPrimaryMenuForLocale('ar'))->pluck('icon')->values()->all();

    expect($arabicIcons)->toBe($englishIcons)
        ->and(count($englishIcons))->toBeGreaterThan(15);
});

it('uses arabic labels in the arabic primary menu', function (): void {
    $menu = adminPrimaryMenuForLocale('ar');

    expect(collect($menu)->pluck('label')->all())
        ->toContain(__('admin.navigation.employee', locale: 'ar'))
        ->toContain(__('admin.navigation.sale', locale: 'ar'))
        ->not->toContain(__('admin.navigation.employee', locale: 'en'));
});

it('links employees to the employees list in arabic', function (): void {
    $menu = adminPrimaryMenuForLocale('ar');

    $employees = collect($menu)->firstWhere('icon', 'icon-employees');

    expect($employees)->not->toBeNull()
        ->and($employees['url'])->toContain('/admin/employees/employees');
});

it('links sales to quotations in arabic and english', function (): void {
    foreach (['ar', 'en'] as $locale) {
        $menu = adminPrimaryMenuForLocale($locale);
        $sales = collect($menu)->firstWhere('icon', 'icon-sales');

        expect($sales)->not->toBeNull()
            ->and($sales['url'])->toContain('/admin/sale/orders/quotations');
    }
});
