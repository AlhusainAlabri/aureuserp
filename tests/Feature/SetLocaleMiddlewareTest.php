<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    config()->set('app.supported_locales', [
        'en' => ['label' => 'English', 'native' => 'English', 'flag' => 'en', 'rtl' => false],
        'ar' => ['label' => 'Arabic', 'native' => 'العربية', 'flag' => 'ar', 'rtl' => true],
    ]);

    config()->set('app.locale', 'ar');
    config()->set('app.fallback_locale', 'en');

    Session::flush();
    Auth::logout();
    App::setLocale('ar');
});

function runSetLocaleMiddleware(array $query = [], array $sessionData = []): string
{
    foreach ($sessionData as $key => $value) {
        Session::put($key, $value);
    }

    $request = Request::create('/admin', 'GET', $query);
    $request->setLaravelSession(app('session.store'));
    $request->setUserResolver(fn () => Auth::user());

    (new SetLocale)->handle($request, fn () => response('ok'));

    return App::getLocale();
}

it('uses authenticated user language instead of stale session locale', function (): void {
    $user = User::withoutEvents(fn () => User::factory()->create(['language' => 'en']));
    Auth::login($user);

    expect(runSetLocaleMiddleware([], ['locale' => 'ar']))->toBe('en')
        ->and(Session::has('locale'))->toBeFalse();
});

it('clears stale session locale for authenticated users', function (): void {
    $user = User::withoutEvents(fn () => User::factory()->create(['language' => null]));
    Auth::login($user);

    runSetLocaleMiddleware([], ['locale' => 'ar']);

    expect(Session::has('locale'))->toBeFalse();
});

it('lets query lang override authenticated user language for one request', function (): void {
    $user = User::withoutEvents(fn () => User::factory()->create(['language' => 'ar']));
    Auth::login($user);

    expect(runSetLocaleMiddleware(['lang' => 'en']))->toBe('en')
        ->and($user->fresh()->language)->toBe('ar');
});
