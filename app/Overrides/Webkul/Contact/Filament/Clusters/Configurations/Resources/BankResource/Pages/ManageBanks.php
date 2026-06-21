<?php

namespace Webkul\Contact\Filament\Clusters\Configurations\Resources\BankResource\Pages;

use App\Filament\Contacts\Concerns\HasContactConfigurationBreadcrumbs;
use App\Filament\Contacts\Concerns\InteractsWithContactConfigurationManagePageTabs;
use Webkul\Contact\Filament\Clusters\Configurations\Resources\BankResource;
use Webkul\Contact\Models\Bank;
use Webkul\Partner\Filament\Resources\BankResource\Pages\ManageBanks as BaseManageBanks;

class ManageBanks extends BaseManageBanks
{
    use HasContactConfigurationBreadcrumbs;
    use InteractsWithContactConfigurationManagePageTabs;

    protected static string $resource = BankResource::class;

    protected static function configurationManagePageTranslationKey(): string
    {
        return 'contacts::filament/clusters/configurations/resources/bank/pages/manage-banks';
    }

    protected static function configurationManagePageModel(): string
    {
        return Bank::class;
    }
}
