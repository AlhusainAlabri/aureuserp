<?php

namespace Webkul\Contact\Filament\Clusters\Configurations\Resources\TagResource\Pages;

use App\Filament\Contacts\Concerns\HasContactConfigurationBreadcrumbs;
use App\Filament\Contacts\Concerns\InteractsWithContactConfigurationManagePageTabs;
use Webkul\Contact\Filament\Clusters\Configurations\Resources\TagResource;
use Webkul\Contact\Models\Tag;
use Webkul\Partner\Filament\Resources\TagResource\Pages\ManageTags as BaseManageTags;

class ManageTags extends BaseManageTags
{
    use HasContactConfigurationBreadcrumbs;
    use InteractsWithContactConfigurationManagePageTabs;

    protected static string $resource = TagResource::class;

    protected static function configurationManagePageTranslationKey(): string
    {
        return 'contacts::filament/clusters/configurations/resources/tag/pages/manage-tags';
    }

    protected static function configurationManagePageModel(): string
    {
        return Tag::class;
    }
}
