<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

it('defaults to arabic when no locale is stored in session', function (): void {
    config(['app.locale' => 'ar']);

    $middleware = new SetLocale;

    $request = Request::create('/admin/login', 'GET');
    $request->setLaravelSession(app('session.store'));

    $middleware->handle($request, fn () => response('ok'));

    expect(App::getLocale())->toBe('ar');
});

it('uses english fallback locale for missing translation keys', function (): void {
    expect(config('app.fallback_locale'))->toBe('en');
});
