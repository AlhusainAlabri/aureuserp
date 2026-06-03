<?php

use App\Filament\Pages\Dashboard;
use App\Support\DashboardNavigationOrder;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Illuminate\Support\Facades\App;
use Webkul\Employee\Filament\Resources\EmployeeResource;

require_once __DIR__.'/../Employees/EmployeeTestHelpers.php';

it('evaluates registered navigation group labels in arabic locale', function (): void {
    App::setLocale('ar');

    $groups = Filament::getPanel('admin')->getNavigationGroups();

    $dashboardGroup = collect($groups)
        ->first(fn (NavigationGroup $group): bool => $group->getIcon() === 'icon-dashboard');

    expect($dashboardGroup)->not->toBeNull()
        ->and($dashboardGroup->getLabel())->toBe(Dashboard::getNavigationGroup())
        ->and($dashboardGroup->getLabel())->toBe(__('admin.navigation.dashboard', locale: 'ar'));
});

it('lists the org dashboard first within the dashboard navigation group', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $dashboardGroup = collect(Filament::getNavigation())
        ->first(fn (NavigationGroup $group): bool => $group->getIcon() === 'icon-dashboard');

    expect($dashboardGroup)->not->toBeNull();

    $labels = DashboardNavigationOrder::sort($dashboardGroup->getItems())
        ->map(fn ($item) => $item->getLabel())
        ->values()
        ->all();

    expect($labels[0])->toBe(Dashboard::getNavigationLabel())
        ->and(Dashboard::getNavigationSort())->toBe(0);
});

it('maps employee navigation items to the registered arabic group with icon', function (): void {
    App::setLocale('ar');

    $user = createEmployeeAdminUser();
    $user->update(['language' => 'ar']);

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $employeeGroupLabel = EmployeeResource::getNavigationGroup();

    $employeeGroup = collect(Filament::getNavigation())
        ->first(fn (NavigationGroup $group): bool => $group->getLabel() === $employeeGroupLabel);

    expect($employeeGroup)->not->toBeNull()
        ->and($employeeGroup->getIcon())->toBe('icon-employees')
        ->and(collect($employeeGroup->getItems())->first()?->getUrl())->not->toBeNull();
});
