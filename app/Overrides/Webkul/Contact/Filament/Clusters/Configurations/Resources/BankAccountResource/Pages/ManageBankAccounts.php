<?php

namespace Webkul\Contact\Filament\Clusters\Configurations\Resources\BankAccountResource\Pages;

use App\Filament\Contacts\Concerns\HasContactConfigurationBreadcrumbs;
use App\Filament\Contacts\Concerns\InteractsWithContactConfigurationManagePageTabs;
use Webkul\Contact\Filament\Clusters\Configurations\Resources\BankAccountResource;
use Webkul\Contact\Models\BankAccount;
use Webkul\Partner\Filament\Resources\BankAccountResource\Pages\ManageBankAccounts as BaseManageBankAccounts;

class ManageBankAccounts extends BaseManageBankAccounts
{
    use HasContactConfigurationBreadcrumbs;
    use InteractsWithContactConfigurationManagePageTabs;

    protected static string $resource = BankAccountResource::class;

    protected static function configurationManagePageTranslationKey(): string
    {
        return 'contacts::filament/clusters/configurations/resources/bank-account/pages/manage-bank-accounts';
    }

    protected static function configurationManagePageModel(): string
    {
        return BankAccount::class;
    }
}
