<?php

namespace App\Support;

class FilamentUrl
{
    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public static function withLocale(array $parameters = []): array
    {
        $locale = app()->getLocale();

        if ($locale !== '' && ! isset($parameters['lang'])) {
            $parameters['lang'] = $locale;
        }

        return $parameters;
    }

    public static function appendLocaleToUrl(string $url): string
    {
        $locale = request()->query('lang', app()->getLocale());

        if (! is_string($locale) || $locale === '' || str_contains($url, 'lang=')) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.http_build_query(['lang' => $locale]);
    }
}
