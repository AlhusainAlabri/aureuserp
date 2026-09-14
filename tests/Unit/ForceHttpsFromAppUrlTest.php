<?php

use Illuminate\Support\Facades\URL;

it('forces https only when the app url is https', function (string $appUrl, string $expectedScheme): void {
    config(['app.url' => $appUrl]);
    URL::forceRootUrl($appUrl);

    if (str_starts_with((string) config('app.url'), 'https://')) {
        URL::forceScheme('https');
    } else {
        URL::forceScheme('http');
    }

    expect(url('/admin/login'))->toStartWith($expectedScheme.'://');
})->with([
    ['https://erp.zseeb.om', 'https'],
    ['http://erp.zseeb.om', 'http'],
]);
