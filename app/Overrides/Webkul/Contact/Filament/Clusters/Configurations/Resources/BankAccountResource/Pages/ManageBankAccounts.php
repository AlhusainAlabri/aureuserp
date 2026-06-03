<?php

namespace Webkul\Contact\Filament\Clusters\Configurations\Resources\BankAccountResource\Pages;

use App\Filament\Contacts\Concerns\HasContactConfigurationBreadcrumbs;
use Webkul\Contact\Filament\Clusters\Configurations\Resources\BankAccountResource;
use Webkul\Partner\Filament\Resources\BankAccountResource\Pages\ManageBankAccounts as BaseManageBankAccounts;

class ManageBankAccounts extends BaseManageBankAccounts
{
    use HasContactConfigurationBreadcrumbs;

    protected static string $resource = BankAccountResource::class;
}
