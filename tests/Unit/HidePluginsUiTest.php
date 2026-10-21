<?php

use App\Http\Middleware\DenyPluginsUiAccess;
use App\Providers\Filament\AdminPanelProvider;
use App\Support\ModuleLauncher\ModuleLauncherItems;
use App\Support\PluginManager\PluginManagerUi;
use App\Support\PluginManager\PluginsNavigationManager;
use Filament\Events\ServingFilament;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Webkul\PluginManager\Filament\Resources\PluginResource;
use Webkul\Security\Models\Permission;
use Webkul\Security\Models\User;

it('reads hide plugins ui from config', function (): void {
    config(['plugin-manager.hide_ui' => true]);

    expect(PluginManagerUi::isHidden())->toBeTrue();

    config(['plugin-manager.hide_ui' => false]);

    expect(PluginManagerUi::isHidden())->toBeFalse();
});

it('omits the plugin navigation group when the ui is hidden', function (): void {
    config(['plugin-manager.hide_ui' => true]);

    $provider = new AdminPanelProvider(app());
    $method = new ReflectionMethod($provider, 'adminNavigationGroups');
    $method->setAccessible(true);

    /** @var array<NavigationGroup> $groups */
    $groups = $method->invoke($provider);

    $pluginLabels = collect($groups)
        ->map(fn (NavigationGroup $group): string => (string) $group->getLabel())
        ->all();

    expect($pluginLabels)->not->toContain(__('admin.navigation.plugin'));
});

it('blocks direct access to admin plugins routes when the ui is hidden', function (): void {
    config([
        'plugin-manager.hide_ui'    => true,
        'plugin-manager.access_key' => 'open-sesame',
    ]);

    $middleware = app(DenyPluginsUiAccess::class);
    $request = Request::create('/admin/plugins', 'GET');

    expect(fn () => $middleware->handle($request, fn () => response('ok')))
        ->toThrow(fn (HttpException $exception): bool => $exception->getStatusCode() === 404);
});

it('rejects the wrong secret and keeps the plugins page hidden', function (): void {
    config([
        'plugin-manager.hide_ui'    => true,
        'plugin-manager.access_key' => 'open-sesame',
    ]);

    $middleware = app(DenyPluginsUiAccess::class);
    $request = pluginsUiRequest('/admin/plugins?access=wrong-word');

    expect(fn () => $middleware->handle($request, fn () => response('ok')))
        ->toThrow(fn (HttpException $exception): bool => $exception->getStatusCode() === 404);
});

it('unlocks the plugins page when the secret is in the url', function (): void {
    config([
        'plugin-manager.hide_ui'    => true,
        'plugin-manager.access_key' => 'open-sesame',
    ]);

    $request = pluginsUiRequest('/admin/plugins?access=open-sesame');
    $middleware = app(DenyPluginsUiAccess::class);
    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->isRedirect())->toBeTrue()
        ->and($response->headers->get('Location'))->toContain('/admin/plugins')
        ->and($response->headers->get('Location'))->not->toContain('access=')
        ->and($request->session()->get(PluginManagerUi::SESSION_KEY))->toBeTrue();
});

it('keeps an unlocked session on later visits without the secret', function (): void {
    config([
        'plugin-manager.hide_ui'    => true,
        'plugin-manager.access_key' => 'open-sesame',
    ]);

    $request = pluginsUiRequest('/admin/plugins');
    $request->session()->put(PluginManagerUi::SESSION_KEY, true);

    $response = app(DenyPluginsUiAccess::class)->handle($request, fn () => response('ok'));

    expect($response->getContent())->toBe('ok');
});

it('removes plugins links from navigation and the dashboard while the ui is hidden', function (): void {
    config(['plugin-manager.hide_ui' => true]);

    $groups = PluginsNavigationManager::withoutPluginGroups([
        NavigationGroup::make('Plugins')
            ->icon('icon-plugin')
            ->items([
                NavigationItem::make('Plugins')->url('http://localhost/admin/plugins'),
            ]),
        NavigationGroup::make('Settings')
            ->icon('icon-settings')
            ->items([
                NavigationItem::make('Companies')->url('http://localhost/admin/companies'),
            ]),
    ]);

    $urls = collect($groups)
        ->flatMap(fn (NavigationGroup $group): array => collect($group->getItems())->map(
            fn (NavigationItem $item): ?string => $item->getUrl(),
        )->all())
        ->filter()
        ->values();

    expect($urls->all())->toBe(['http://localhost/admin/companies']);

    $user = pluginsUiAdmin();
    $this->actingAs($user);

    app()->forgetInstance(NavigationManager::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    ServingFilament::dispatch();

    $launcherUrls = ModuleLauncherItems::forCurrentUser()->pluck('url');

    expect($launcherUrls->contains(
        fn (string $url): bool => PluginManagerUi::isPluginsPath($url),
    ))->toBeFalse();

    pluginsUiRememberUnlock();

    app()->forgetInstance(NavigationManager::class);

    $unlockedUrls = ModuleLauncherItems::forCurrentUser()->pluck('url');

    expect($unlockedUrls->contains(
        fn (string $url): bool => PluginManagerUi::isPluginsPath($url),
    ))->toBeFalse();
});

it('shows the plugins page to a logged-in admin after the secret unlocks the session', function (): void {
    config([
        'plugin-manager.hide_ui'    => true,
        'plugin-manager.access_key' => 'open-sesame',
    ]);

    $user = pluginsUiAdmin();
    $permission = Permission::firstOrCreate([
        'name'       => 'view_any_plugin_manager_plugin',
        'guard_name' => 'web',
    ]);
    $user->givePermissionTo($permission);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->get('/admin/plugins')->assertNotFound();

    expect(PluginResource::canAccess())->toBeFalse();

    pluginsUiRememberUnlock();

    expect(PluginResource::canAccess())->toBeTrue()
        ->and(Gate::check('view_any_plugin_manager_plugin'))->toBeTrue();

    $this->withSession([
        PluginManagerUi::SESSION_KEY => true,
    ])->get('/admin/plugins')->assertSuccessful();
});

function pluginsUiAdmin(): User
{
    return User::where('email', 'nodhumtech@gmail.com')->first()
        ?? User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
}

function pluginsUiRequest(string $uri): Request
{
    $session = app('session')->driver();
    $session->start();

    $request = Request::create($uri, 'GET');
    $request->setLaravelSession($session);

    return $request;
}

function pluginsUiRememberUnlock(): void
{
    $session = app('session')->driver();
    $session->start();
    $session->put(PluginManagerUi::SESSION_KEY, true);
    request()->setLaravelSession($session);
}

it('denies plugin manager shield permissions when the ui is hidden', function (): void {
    config(['plugin-manager.hide_ui' => true]);

    expect(Gate::check('view_any_plugin_manager_plugin'))->toBeFalse();
});

it('denies plugin resource access when the ui is hidden', function (): void {
    config(['plugin-manager.hide_ui' => true]);

    $user = User::where('email', 'nodhumtech@gmail.com')->first()
        ?? User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));

    $this->actingAs($user);

    expect(PluginResource::canAccess())->toBeFalse();
});
