<?php

namespace Webkul\Contact\Filament\Clusters\Configurations\Resources;

use App\Filament\Contacts\Concerns\ProvidesContactConfigurationResourceLabels;
use Webkul\Contact\Filament\Clusters\Configurations;
use Webkul\Contact\Filament\Clusters\Configurations\Resources\BankAccountResource\Pages\ManageBankAccounts;
use Webkul\Contact\Models\BankAccount;
use Webkul\Partner\Filament\Resources\BankAccountResource as BaseBankAccountResource;

class BankAccountResource extends BaseBankAccountResource
{
    use ProvidesContactConfigurationResourceLabels;

    protected static ?string $model = BankAccount::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 5;

    protected static ?string $cluster = Configurations::class;

    protected static function contactConfigurationTranslationKey(): string
    {
        return 'contacts::filament/clusters/configurations/resources/bank-account';
    }

    public static function getNavigationGroup(): string
    {
        return __('contacts::filament/clusters/configurations/resources/bank-account.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('contacts::filament/clusters/configurations/resources/bank-account.navigation.title');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBankAccounts::route('/'),
        ];
    }
}
