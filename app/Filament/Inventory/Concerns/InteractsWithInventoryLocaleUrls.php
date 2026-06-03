<?php

namespace App\Filament\Inventory\Concerns;

use App\Support\FilamentUrl;

trait InteractsWithInventoryLocaleUrls
{
    protected function localizedPageUrl(string $pageClass, array $parameters = []): string
    {
        return FilamentUrl::appendLocaleToUrl(
            $pageClass::getUrl($parameters),
        );
    }

    /**
     * @param  class-string<\Filament\Resources\Resource>  $resourceClass
     */
    protected function localizedResourceUrl(string $resourceClass, string $name = 'index', array $parameters = []): string
    {
        return FilamentUrl::appendLocaleToUrl(
            $resourceClass::getUrl($name, FilamentUrl::withLocale($parameters)),
        );
    }
}
