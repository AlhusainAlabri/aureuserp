<?php

namespace Webkul\Contact\Filament\Clusters\Configurations\Resources\TagResource\Pages;

use App\Filament\Contacts\Concerns\HasContactConfigurationBreadcrumbs;
use Webkul\Contact\Filament\Clusters\Configurations\Resources\TagResource;
use Webkul\Partner\Filament\Resources\TagResource\Pages\ManageTags as BaseManageTags;

class ManageTags extends BaseManageTags
{
    use HasContactConfigurationBreadcrumbs;

    protected static string $resource = TagResource::class;
}
