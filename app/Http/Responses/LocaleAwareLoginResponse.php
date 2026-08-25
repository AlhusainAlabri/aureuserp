<?php

namespace App\Http\Responses;

use App\Support\FilamentUrl;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as Responsable;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Livewire\Features\SupportRedirects\Redirector;

class LocaleAwareLoginResponse implements Responsable
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        /** @var Request $request */
        $supported = array_keys(config('app.supported_locales', []));
        $locale = $this->resolveLocale($request, $supported);

        $user = $request->user();

        if ($user !== null && is_string($locale) && in_array($locale, $supported, true)) {
            if (($user->language ?? null) !== $locale) {
                $user->forceFill(['language' => $locale])->save();
            }

            Session::forget('locale');
            app()->setLocale($locale);
        }

        $target = $request->session()->pull('url.intended', Filament::getUrl());

        if (! is_string($target)) {
            $target = Filament::getUrl();
        }

        return redirect()->to(FilamentUrl::appendLocaleToUrl($target));
    }

    /**
     * @param  array<int, string>  $supported
     */
    protected function resolveLocale(Request $request, array $supported): ?string
    {
        $candidates = [
            $request->query('lang'),
            Session::get('locale'),
            $request->user()?->language,
            app()->getLocale(),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && in_array($candidate, $supported, true)) {
                return $candidate;
            }
        }

        return null;
    }
}
