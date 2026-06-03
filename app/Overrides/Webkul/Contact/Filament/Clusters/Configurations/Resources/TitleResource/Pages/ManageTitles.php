<?php

namespace Webkul\Contact\Filament\Clusters\Configurations\Resources\TitleResource\Pages;

use App\Filament\Contacts\Concerns\HasContactConfigurationBreadcrumbs;
use Webkul\Contact\Filament\Clusters\Configurations\Resources\TitleResource;
use Webkul\Partner\Filament\Resources\TitleResource\Pages\ManageTitles as BaseManageTitles;

class ManageTitles extends BaseManageTitles
{
    use HasContactConfigurationBreadcrumbs;

    protected static string $resource = TitleResource::class;
}
