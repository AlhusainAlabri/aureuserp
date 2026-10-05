<?php

use App\Http\Middleware\DenyPluginsUiAccess;
use App\Providers\Filament\AdminPanelProvider;
use App\Support\PluginManager\PluginManagerUi;
use Filament\Navigation\NavigationGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Webkul\PluginManager\Filament\Resources\PluginResource;
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
    config(['plugin-manager.hide_ui' => true]);

    $middleware = app(DenyPluginsUiAccess::class);
    $request = Request::create('/admin/plugins', 'GET');

    expect(fn () => $middleware->handle($request, fn () => response('ok')))
        ->toThrow(HttpException::class);
});

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
