<?php

namespace Webkul\Contact\Filament\Clusters\Configurations\Resources\IndustryResource\Pages;

use App\Filament\Contacts\Concerns\HasContactConfigurationBreadcrumbs;
use Webkul\Contact\Filament\Clusters\Configurations\Resources\IndustryResource;
use Webkul\Partner\Filament\Resources\IndustryResource\Pages\ManageIndustries as BaseManageIndustries;

class ManageIndustries extends BaseManageIndustries
{
    use HasContactConfigurationBreadcrumbs;

    protected static string $resource = IndustryResource::class;
}
