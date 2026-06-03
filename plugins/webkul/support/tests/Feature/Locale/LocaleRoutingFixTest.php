<?php

use App\Http\Controllers\SetUserLocaleController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Webkul\Security\Models\User;
use Webkul\Support\Filament\Widgets\RecordNavigationTabs;

require_once __DIR__.'/../../Helpers/SecurityHelper.php';
require_once __DIR__.'/../../Helpers/TestBootstrapHelper.php';

beforeEach(function () {
    TestBootstrapHelper::ensureERPInstalled();

    config()->set('app.supported_locales', [
        'en' => ['label' => 'English', 'native' => 'English', 'flag' => 'en', 'rtl' => false],
        'ar' => ['label' => 'Arabic',  'native' => 'العربية',  'flag' => 'ar', 'rtl' => true],
    ]);

    Session::flush();
    Auth::logout();
});

function invokeLocaleSwitch(string $locale, array $query = [], ?User $user = null): RedirectResponse
{
    $request = Request::create("/locale/{$locale}", 'GET', $query);
    $request->setLaravelSession(app('session.store'));
    $request->setUserResolver(fn () => $user);

    return app(SetUserLocaleController::class)($request, $locale);
}

it('persists locale preference for authenticated users and redirects back', function () {
    $user = User::factory()->create(['language' => 'en']);

    $redirect = url('/admin/inventory/operations/receipts');

    $response = invokeLocaleSwitch('ar', ['redirect' => $redirect], $user);

    expect($response->getTargetUrl())->toBe($redirect);
    expect($user->fresh()->language)->toBe('ar');
    expect(Session::has('locale'))->toBeFalse();
});

it('stores guest locale in session', function () {
    $redirect = url('/');

    $response = invokeLocaleSwitch('ar', ['redirect' => $redirect]);

    expect($response->getTargetUrl())->toBe($redirect);
    expect(Session::get('locale'))->toBe('ar');
});

it('rejects unsupported locales', function () {
    invokeLocaleSwitch('fr');
})->throws(NotFoundHttpException::class);

it('strips lang query parameter from redirect url', function () {
    $user = User::factory()->create(['language' => 'en']);

    $redirect = url('/admin?lang=en&foo=bar');

    $response = invokeLocaleSwitch('ar', ['redirect' => $redirect], $user);

    expect($response->getTargetUrl())->toBe(url('/admin?foo=bar'));
});

it('rejects external redirect urls', function () {
    $user = User::factory()->create(['language' => 'en']);

    $response = invokeLocaleSwitch('ar', [
        'redirect' => 'https://evil.example.com/admin',
    ], $user);

    expect($response->getTargetUrl())->toBe(url('/'));
});

it('renders record navigation tabs without segmented tab classes', function () {
    Livewire::test(RecordNavigationTabs::class, [
        'navigationItems' => [
            [
                'label'      => 'View',
                'url'        => '/admin/example/1',
                'isActive'   => true,
                'isHidden'   => false,
                'icon'       => 'heroicon-o-eye',
                'activeIcon' => null,
                'badge'      => null,
                'badgeColor' => null,
            ],
            [
                'label'      => 'Moves',
                'url'        => '/admin/example/1/moves',
                'isActive'   => false,
                'isHidden'   => false,
                'icon'       => 'heroicon-o-arrows-right-left',
                'activeIcon' => null,
                'badge'      => null,
                'badgeColor' => null,
            ],
        ],
    ])
        ->assertSeeHtml('record-navigation-tabs')
        ->assertSeeHtml('href="/admin/example/1/moves"')
        ->assertDontSeeHtml('fi-tabs-segmented');
});

it('hides navigation items marked as hidden in the widget output', function () {
    Livewire::test(RecordNavigationTabs::class, [
        'navigationItems' => [
            [
                'label'      => 'Visible',
                'url'        => '/admin/example/1',
                'isActive'   => true,
                'isHidden'   => false,
                'icon'       => null,
                'activeIcon' => null,
                'badge'      => null,
                'badgeColor' => null,
            ],
            [
                'label'      => 'Hidden',
                'url'        => '/admin/example/1/hidden',
                'isActive'   => false,
                'isHidden'   => true,
                'icon'       => null,
                'activeIcon' => null,
                'badge'      => null,
                'badgeColor' => null,
            ],
        ],
    ])
        ->assertSee('Visible')
        ->assertDontSee('Hidden')
        ->assertDontSeeHtml('/admin/example/1/hidden');
});

it('uses english receipt plural translation key', function () {
    app()->setLocale('en');

    expect(__('inventories::models/receipt.plural'))->toBe('Receipts');
});

it('uses arabic receipt plural translation key', function () {
    app()->setLocale('ar');

    expect(__('inventories::models/receipt.plural'))->toBe('الاستلامات');
});
