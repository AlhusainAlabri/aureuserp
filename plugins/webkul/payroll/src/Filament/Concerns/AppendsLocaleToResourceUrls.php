<?php

namespace Webkul\Payroll\Filament\Concerns;

use App\Support\FilamentUrl;
use Illuminate\Database\Eloquent\Model;

trait AppendsLocaleToResourceUrls
{
    public static function getUrl(
        ?string $name = null,
        array $parameters = [],
        bool $isAbsolute = true,
        ?string $panel = null,
        ?Model $tenant = null,
        bool $shouldGuessMissingParameters = false,
        ?string $configuration = null,
    ): string {
        if (class_exists(FilamentUrl::class)) {
            $parameters = FilamentUrl::withLocale($parameters);
        }

        $url = parent::getUrl(
            $name,
            $parameters,
            $isAbsolute,
            $panel,
            $tenant,
            $shouldGuessMissingParameters,
            $configuration,
        );

        if (class_exists(FilamentUrl::class)) {
            return FilamentUrl::appendLocaleToUrl($url);
        }

        return $url;
    }
}
