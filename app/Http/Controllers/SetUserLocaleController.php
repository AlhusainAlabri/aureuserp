<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetUserLocaleController
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        $supported = array_keys(config('app.supported_locales', []));

        if (! in_array($locale, $supported, true)) {
            abort(404);
        }

        $user = $request->user();

        if ($user !== null) {
            if ($user->language !== $locale) {
                $user->forceFill(['language' => $locale])->save();
            }

            Session::forget('locale');
        } else {
            Session::put('locale', $locale);
        }

        App::setLocale($locale);

        return redirect()->to($this->resolveRedirectUrl($request));
    }

    protected function resolveRedirectUrl(Request $request): string
    {
        $redirect = $request->query('redirect', url()->previous() ?: url('/'));

        if (! is_string($redirect) || $redirect === '') {
            return url('/');
        }

        $parsed = parse_url($redirect);

        if (! isset($parsed['host']) || $parsed['host'] !== $request->getHost()) {
            return url('/');
        }

        $path = $parsed['path'] ?? '/';
        $query = [];

        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $query);
            unset($query['lang']);
        }

        $url = $path;

        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        return $url;
    }
}
