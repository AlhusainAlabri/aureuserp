<?php

namespace Webkul\Contact\Filament\Resources;

use App\Filament\Contacts\RelationManagers\ContactAddressesRelationManager;
use App\Filament\Contacts\RelationManagers\ContactContactsRelationManager;
use App\Filament\Extensions\PartnerResourceExtensions;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Webkul\Contact\Filament\Resources\PartnerResource\Pages\CreatePartner;
use Webkul\Contact\Filament\Resources\PartnerResource\Pages\EditPartner;
use Webkul\Contact\Filament\Resources\PartnerResource\Pages\ListPartners;
use Webkul\Contact\Filament\Resources\PartnerResource\Pages\ManageAddresses;
use Webkul\Contact\Filament\Resources\PartnerResource\Pages\ManageContacts;
use Webkul\Contact\Filament\Resources\PartnerResource\Pages\ViewPartner;
use Webkul\Contact\Models\Partner;
use Webkul\Partner\Filament\Resources\PartnerResource as BasePartnerResource;

class PartnerResource extends BasePartnerResource
{
    protected static ?string $model = Partner::class;

    protected static ?string $slug = 'contact/contacts';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getNavigationLabel(): string
    {
        return __('contacts::filament/resources/partner.navigation.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('contacts::filament/resources/partner.navigation.group');
    }

    public static function getModelLabel(): string
    {
        return __('contacts::filament/resources/partner.model.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('contacts::filament/resources/partner.navigation.title');
    }

    public static function form(Schema $schema): Schema
    {
        return PartnerResourceExtensions::localizeForm(parent::form($schema));
    }

    public static function infolist(Schema $schema): Schema
    {
        return PartnerResourceExtensions::localizeInfolist(parent::infolist($schema));
    }

    public static function table(Table $table): Table
    {
        return PartnerResourceExtensions::localizeTable(parent::table($table));
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewPartner::class,
            EditPartner::class,
            ManageContacts::class,
            ManageAddresses::class,
        ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationGroup::make(__('contacts::filament/resources/partner.relations.contacts'), [
                ContactContactsRelationManager::class,
            ])
                ->icon('heroicon-o-users'),

            RelationGroup::make(__('contacts::filament/resources/partner.relations.addresses'), [
                ContactAddressesRelationManager::class,
            ])
                ->icon('heroicon-o-map-pin'),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'     => ListPartners::route('/'),
            'create'    => CreatePartner::route('/create'),
            'view'      => ViewPartner::route('/{record}'),
            'edit'      => EditPartner::route('/{record}/edit'),
            'contacts'  => ManageContacts::route('/{record}/contacts'),
            'addresses' => ManageAddresses::route('/{record}/addresses'),
        ];
    }
}
