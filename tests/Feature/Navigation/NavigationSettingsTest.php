<?php

use App\Enums\NavigationAppearance;
use App\Enums\NavigationMenuType;
use App\Enums\SidebarDefaultState;
use App\Filament\Clusters\Settings\Pages\ManageNavigation;
use App\Settings\NavigationSettings;
use App\Support\ExtensionPermissionsSync;
use App\Support\Navigation\NavigationMode;
use App\Support\Navigation\SidebarNavigationGroups;
use Filament\Events\ServingFilament;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationManager;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Spatie\LaravelSettings\Exceptions\MissingSettings;
use Webkul\Security\Models\User;

function ensureNavigationSettings(): NavigationSettings
{
    try {
        return app(NavigationSettings::class);
    } catch (MissingSettings) {
        Artisan::call('migrate', [
            '--path'           => 'database/settings',
            '--force'          => true,
            '--no-interaction' => true,
        ]);

        Artisan::call('settings:clear-cache');

        return app(NavigationSettings::class);
    }
}

beforeEach(function (): void {
    $settings = ensureNavigationSettings();
    $settings->menu_type = NavigationMenuType::AppGrid;
    $settings->sidebar_default_state = SidebarDefaultState::Auto;
    $settings->menu_appearance = NavigationAppearance::System;
    $settings->save();
});

it('resolves navigation mode from persisted settings', function (): void {
    $settings = app(NavigationSettings::class);
    $settings->menu_type = NavigationMenuType::Sidebar;
    $settings->sidebar_default_state = SidebarDefaultState::Collapsed;
    $settings->menu_appearance = NavigationAppearance::Dark;
    $settings->save();

    $mode = NavigationMode::resolve();

    expect($mode->menuType)->toBe(NavigationMenuType::Sidebar)
        ->and($mode->sidebarDefaultState)->toBe(SidebarDefaultState::Collapsed)
        ->and($mode->appearance)->toBe(NavigationAppearance::Dark)
        ->and($mode->usesModuleSidebar())->toBeTrue()
        ->and($mode->usesTopNavigation())->toBeFalse()
        ->and($mode->usesAppGrid())->toBeFalse();
});

it('identifies top navigation modes correctly', function (): void {
    $settings = app(NavigationSettings::class);
    $settings->menu_type = NavigationMenuType::TopNavigation;
    $settings->save();

    $mode = NavigationMode::resolve();

    expect($mode->usesTopNavigation())->toBeTrue()
        ->and($mode->usesAppGrid())->toBeFalse()
        ->and($mode->usesModuleSidebar())->toBeFalse();
});

it('configures the admin panel layout based on navigation mode', function (): void {
    $settings = app(NavigationSettings::class);
    $settings->menu_type = NavigationMenuType::Sidebar;
    $settings->save();

    app()->forgetInstance(NavigationManager::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    ServingFilament::dispatch();

    expect(Filament::getCurrentPanel()->hasTopNavigation())->toBeFalse()
        ->and(Filament::getCurrentPanel()->isSidebarCollapsibleOnDesktop())->toBeTrue();

    $settings->menu_type = NavigationMenuType::AppGrid;
    $settings->save();

    app()->forgetInstance(NavigationManager::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    ServingFilament::dispatch();

    expect(Filament::getCurrentPanel()->hasTopNavigation())->toBeTrue();
});

it('filters sidebar groups to the active module outside sidebar mode', function (): void {
    $navigation = [
        NavigationGroup::make('Dashboard')->icon('icon-dashboard')->items([]),
        NavigationGroup::make('Sales')->icon('icon-sales')->items([]),
    ];

    $groups = SidebarNavigationGroups::visibleGroups($navigation, includeAllGroups: false);

    expect($groups)->toHaveCount(0);
});

it('persists navigation settings updates', function (): void {
    $settings = ensureNavigationSettings();
    $settings->menu_type = NavigationMenuType::Sidebar;
    $settings->sidebar_default_state = SidebarDefaultState::Expanded;
    $settings->menu_appearance = NavigationAppearance::Light;
    $settings->save();

    $reloaded = app(NavigationSettings::class);

    expect($reloaded->menu_type)->toBe(NavigationMenuType::Sidebar)
        ->and($reloaded->sidebar_default_state)->toBe(SidebarDefaultState::Expanded)
        ->and($reloaded->menu_appearance)->toBe(NavigationAppearance::Light)
        ->and(NavigationMode::resolve()->usesModuleSidebar())->toBeTrue();
});

it('registers the manage navigation settings page', function (): void {
    expect(ManageNavigation::getNavigationLabel())->toBe(__('navigation-settings.navigation.label'));
});

it('registers the sidebar initialization scripts', function (): void {
    $settings = app(NavigationSettings::class);
    $settings->menu_type = NavigationMenuType::Sidebar;
    $settings->save();

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    ServingFilament::dispatch();

    expect((string) FilamentView::renderHook(PanelsRenderHook::SCRIPTS_BEFORE))
        ->toContain('collapsedGroupsKey')
        ->toContain('erpModuleSidebarDefaultsVersion')
        ->toContain('defaultsState')
        ->and((string) FilamentView::renderHook(PanelsRenderHook::SCRIPTS_AFTER))
        ->toContain('patchFilamentSidebarStore')
        ->toContain('registerErpNavigationFilterStore')
        ->toContain('applyErpNavigationFilter')
        ->toContain('syncErpModuleSidebarCollapsedGroups')
        ->toContain("document.addEventListener('alpine:initialized', initializeErpNavigation)")
        ->toContain("const defaultState = 'auto';");
});

it('allows access for users with the shield page permission', function (): void {
    app(ExtensionPermissionsSync::class)->syncNavigation();

    $user = User::factory()->create();
    $user->givePermissionTo('page_filament_manage_navigation');

    $this->actingAs($user);

    expect(ManageNavigation::canAccess())->toBeTrue();
});

it('denies access for users without the page permission', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    expect(ManageNavigation::canAccess())->toBeFalse();
});

it('renders the navigation settings page for authorized users', function (): void {
    app(ExtensionPermissionsSync::class)->syncNavigation();

    $user = User::factory()->create();
    $user->givePermissionTo('page_filament_manage_navigation');

    $this->actingAs($user);

    Livewire::test(ManageNavigation::class)
        ->assertSuccessful()
        ->assertSee(__('navigation-settings.form.menu_type.label'));
});

it('shows Arabic labels when locale is ar', function (): void {
    app(ExtensionPermissionsSync::class)->syncNavigation();
    app()->setLocale('ar');

    $user = User::factory()->create();
    $user->givePermissionTo('page_filament_manage_navigation');

    $this->actingAs($user);

    Livewire::test(ManageNavigation::class)
        ->assertSee(__('navigation-settings.form.menu_type.label'));
});

it('persists navigation settings from the settings page', function (): void {
    app(ExtensionPermissionsSync::class)->syncNavigation();

    $settings = ensureNavigationSettings();
    $settings->menu_type = NavigationMenuType::Sidebar;
    $settings->save();

    $user = User::factory()->create();
    $user->givePermissionTo('page_filament_manage_navigation');

    $this->actingAs($user);

    Livewire::test(ManageNavigation::class)
        ->fillForm([
            'sidebar_default_state' => SidebarDefaultState::Collapsed->value,
            'menu_appearance'       => NavigationAppearance::Dark->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    Artisan::call('settings:clear-cache');

    $reloaded = app(NavigationSettings::class);

    expect($reloaded->menu_type)->toBe(NavigationMenuType::Sidebar)
        ->and($reloaded->sidebar_default_state)->toBe(SidebarDefaultState::Collapsed)
        ->and($reloaded->menu_appearance)->toBe(NavigationAppearance::Dark);
});

it('exposes sidebar data attributes for the blade layer', function (): void {
    $settings = app(NavigationSettings::class);
    $settings->menu_type = NavigationMenuType::Sidebar;
    $settings->sidebar_default_state = SidebarDefaultState::Auto;
    $settings->menu_appearance = NavigationAppearance::System;
    $settings->save();

    expect(NavigationMode::resolve()->sidebarDataAttributes())->toBe([
        'data-nav-mode'        => 'sidebar',
        'data-nav-appearance'  => 'system',
        'data-sidebar-default' => 'auto',
    ]);
});
