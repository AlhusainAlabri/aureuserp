<?php

namespace Webkul\Contact\Filament\Clusters\Configurations\Resources\IndustryResource\Pages;

use App\Filament\Contacts\Concerns\HasContactConfigurationBreadcrumbs;
use App\Filament\Contacts\Concerns\InteractsWithContactConfigurationManagePageTabs;
use Webkul\Contact\Filament\Clusters\Configurations\Resources\IndustryResource;
use Webkul\Contact\Models\Industry;
use Webkul\Partner\Filament\Resources\IndustryResource\Pages\ManageIndustries as BaseManageIndustries;

class ManageIndustries extends BaseManageIndustries
{
    use HasContactConfigurationBreadcrumbs;
    use InteractsWithContactConfigurationManagePageTabs;

    protected static string $resource = IndustryResource::class;

    protected static function configurationManagePageTranslationKey(): string
    {
        return 'contacts::filament/clusters/configurations/resources/industry/pages/manage-industries';
    }

    protected static function configurationManagePageModel(): string
    {
        return Industry::class;
    }
}
