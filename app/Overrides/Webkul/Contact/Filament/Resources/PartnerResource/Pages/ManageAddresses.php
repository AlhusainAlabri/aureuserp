<?php

namespace Webkul\Contact\Filament\Resources\PartnerResource\Pages;

use App\Filament\Extensions\PartnerResourceExtensions;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Webkul\Contact\Filament\Resources\PartnerResource;
use Webkul\Partner\Enums\AccountType;
use Webkul\Partner\Filament\Resources\AddressResource;
use Webkul\Partner\Filament\Resources\PartnerResource\Pages\ManageAddresses as BaseManageAddresses;

class ManageAddresses extends BaseManageAddresses
{
    protected static string $resource = PartnerResource::class;

    public static function getRelationshipTitle(): string
    {
        return __('contacts::filament/resources/partner.relations.addresses');
    }

    public function getTitle(): string|Htmlable
    {
        return __('contacts::filament/resources/partner.pages.manage-addresses.title', [
            'name' => $this->getRecordTitle(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return PartnerResourceExtensions::localizeAddressForm(AddressResource::form($schema));
    }

    public function table(Table $table): Table
    {
        return parent::table($table)
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
