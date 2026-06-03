<?php

namespace Webkul\Contact\Filament\Clusters\Configurations\Resources\BankResource\Pages;

use App\Filament\Contacts\Concerns\HasContactConfigurationBreadcrumbs;
use Webkul\Contact\Filament\Clusters\Configurations\Resources\BankResource;
use Webkul\Partner\Filament\Resources\BankResource\Pages\ManageBanks as BaseManageBanks;

class ManageBanks extends BaseManageBanks
{
    use HasContactConfigurationBreadcrumbs;

    protected static string $resource = BankResource::class;
}
