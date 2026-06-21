<?php

namespace Webkul\Contact\Filament\Clusters\Configurations\Resources\TitleResource\Pages;

use App\Filament\Contacts\Concerns\HasContactConfigurationBreadcrumbs;
use App\Filament\Contacts\Concerns\InteractsWithContactConfigurationManagePage;
use Webkul\Contact\Filament\Clusters\Configurations\Resources\TitleResource;
use Webkul\Partner\Filament\Resources\TitleResource\Pages\ManageTitles as BaseManageTitles;

class ManageTitles extends BaseManageTitles
{
    use HasContactConfigurationBreadcrumbs;
    use InteractsWithContactConfigurationManagePage;

    protected static string $resource = TitleResource::class;

    protected static function configurationManagePageTranslationKey(): string
    {
        return 'contacts::filament/clusters/configurations/resources/title/pages/manage-titles';
    }
}
