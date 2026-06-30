<?php

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\ModuleLauncher;
use App\Filament\Resources\DashboardShortcutResource;
use App\Models\DashboardShortcut;
use App\Support\ModuleLauncher\ModuleLauncherItems;
use App\Support\ModuleLauncher\ModuleLauncherPreferenceStore;
use Filament\Events\ServingFilament;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationManager;
use Livewire\Livewire;
use Webkul\Security\Models\User;

it('registers module launcher as the admin home page', function (): void {
    expect(ModuleLauncher::getUrl())->toBe(url('/admin'))
        ->and(Dashboard::getUrl())->toContain('/admin/org-overview');
});

it('renders the module launcher for authenticated users', function (): void {
    $user = User::where('email', 'nodhumtech@gmail.com')->first()
        ?? User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));

    $this->actingAs($user);

    Livewire::test(ModuleLauncher::class)
        ->assertSuccessful()
        ->assertSee(__('module-launcher.greeting', ['name' => $user->name]));
});

it('includes permission-based modules and active shortcuts', function (): void {
    $user = User::where('email', 'nodhumtech@gmail.com')->first()
        ?? User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));

    $this->actingAs($user);

    app()->forgetInstance(NavigationManager::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    ServingFilament::dispatch();

    $shortcut = DashboardShortcut::factory()->create([
        'title_en' => 'External Portal',
        'title_ar' => 'البوابة الخارجية',
        'url'      => 'https://example.com/portal',
        'icon'     => 'heroicon-o-arrow-top-right-on-square',
        'color'    => 'info',
        'sort'     => 1,
    ]);

    $items = ModuleLauncherItems::forCurrentUser();

    expect($items->count())->toBeGreaterThan(5)
        ->and($items->firstWhere('label', 'External Portal'))->not->toBeNull()
        ->and($items->firstWhere('label', 'External Portal')['url'])->toBe('https://example.com/portal');

    Livewire::test(ModuleLauncher::class)
        ->assertSuccessful()
        ->assertSee('External Portal');

    $shortcut->update(['is_active' => false]);

    expect(ModuleLauncherItems::forCurrentUser()->firstWhere('label', 'External Portal'))->toBeNull();
});

it('restricts shortcut management to administrators', function (): void {
    $admin = User::where('email', 'nodhumtech@gmail.com')->first();

    if ($admin === null) {
        $this->markTestSkipped('Admin user not found.');
    }

    $this->actingAs($admin);

    expect(DashboardShortcutResource::canAccess())->toBeTrue();

    $employee = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    $this->actingAs($employee);

    expect(DashboardShortcutResource::canAccess())->toBeFalse();
});

it('uses arabic shortcut titles when locale is arabic', function (): void {
    app()->setLocale('ar');

    $shortcut = DashboardShortcut::factory()->create([
        'title_en' => 'English Title',
        'title_ar' => 'عنوان عربي',
    ]);

    expect($shortcut->getTitle())->toBe('عنوان عربي');
});

it('lets users hide and restore module launcher items', function (): void {
    $user = User::where('email', 'nodhumtech@gmail.com')->first()
        ?? User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));

    $this->actingAs($user);

    app()->forgetInstance(NavigationManager::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    ServingFilament::dispatch();

    $shortcut = DashboardShortcut::factory()->create([
        'title_en' => 'Hidden Shortcut Test',
        'url'      => 'https://example.com/hidden',
        'icon'     => 'heroicon-o-link',
    ]);

    $allKeys = ModuleLauncherItems::allForCurrentUser()->pluck('key')->all();
    $shortcutKey = ModuleLauncherItems::shortcutKey($shortcut->id);

    expect($allKeys)->toContain($shortcutKey);

    ModuleLauncherPreferenceStore::syncVisibleItems(
        $allKeys,
        array_values(array_diff($allKeys, [$shortcutKey])),
    );

    expect(ModuleLauncherItems::visibleForCurrentUser()->pluck('key')->all())
        ->not->toContain($shortcutKey);

    Livewire::test(ModuleLauncher::class)
        ->assertSuccessful()
        ->assertDontSee('Hidden Shortcut Test');

    ModuleLauncherPreferenceStore::reset();

    expect(ModuleLauncherItems::visibleForCurrentUser()->pluck('key')->all())
        ->toContain($shortcutKey);

    Livewire::test(ModuleLauncher::class)
        ->assertSuccessful()
        ->assertSee('Hidden Shortcut Test');
});

it('opens the customize launcher slide-over action', function (): void {
    $user = User::where('email', 'nodhumtech@gmail.com')->first()
        ?? User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));

    $this->actingAs($user);

    app()->forgetInstance(NavigationManager::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    ServingFilament::dispatch();

    Livewire::test(ModuleLauncher::class)
        ->assertSuccessful()
        ->mountAction('customizeLauncher')
        ->assertActionMounted('customizeLauncher')
        ->assertMountedActionModalSee(__('module-launcher.customize.heading'));
});
