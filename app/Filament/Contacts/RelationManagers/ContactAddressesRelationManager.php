<?php

namespace App\Filament\Contacts\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Webkul\Partner\Enums\AccountType;
use Webkul\Partner\Filament\Resources\AddressResource;
use Webkul\Partner\Filament\Resources\PartnerResource\RelationManagers\AddressesRelationManager as BaseAddressesRelationManager;

class ContactAddressesRelationManager extends BaseAddressesRelationManager
{
    public static function getRelationshipTitle(): string
    {
        return __('contacts::filament/resources/partner.relations.addresses');
    }

    public function form(Schema $schema): Schema
    {
        return AddressResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return AddressResource::table($table)
            ->modelLabel(__('partners::filament/resources/address.form.name'))
            ->pluralModelLabel(__('contacts::filament/resources/partner.relations.addresses'))
            ->emptyStateHeading(__('contacts::filament/resources/partner.pages.manage-addresses.empty.heading'))
            ->emptyStateDescription(__('contacts::filament/resources/partner.pages.manage-addresses.empty.description'))
            ->headerActions([
                CreateAction::make()
                    ->label(__('partners::filament/resources/address.table.header-actions.create.label'))
                    ->icon('heroicon-o-plus-circle')
                    ->modalHeading(__('contacts::filament/resources/partner.pages.manage-addresses.create-modal.heading'))
                    ->mutateDataUsing(function (array $data): array {
                        $data['account_type'] = AccountType::ADDRESS;

                        return $data;
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('partners::filament/resources/address.table.header-actions.create.notification.title'))
                            ->body(__('partners::filament/resources/address.table.header-actions.create.notification.body')),
                    ),
            ]);
    }
}
