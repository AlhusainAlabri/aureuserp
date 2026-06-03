<?php

namespace App\Filament\Contacts\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Webkul\Contact\Filament\Resources\PartnerResource as ContactPartnerResource;
use Webkul\Partner\Filament\Resources\PartnerResource\RelationManagers\ContactsRelationManager as BaseContactsRelationManager;

class ContactContactsRelationManager extends BaseContactsRelationManager
{
    public static function getRelationshipTitle(): string
    {
        return __('contacts::filament/resources/partner.relations.contacts');
    }

    public function form(Schema $schema): Schema
    {
        return ContactPartnerResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return ContactPartnerResource::table($table)
            ->filters([])
            ->groups([])
            ->modelLabel(__('contacts::filament/resources/partner.model.single'))
            ->pluralModelLabel(__('contacts::filament/resources/partner.model.single'))
            ->emptyStateHeading(__('contacts::filament/resources/partner.pages.manage-contacts.empty.heading'))
            ->emptyStateDescription(__('contacts::filament/resources/partner.pages.manage-contacts.empty.description'))
            ->headerActions([
                CreateAction::make()
                    ->label(__('partners::filament/resources/partner/relation-managers/contacts.table.header-actions.create.label'))
                    ->icon('heroicon-o-plus-circle')
                    ->modalHeading(__('partners::filament/resources/partner/relation-managers/contacts.table.header-actions.create.label'))
                    ->mutateDataUsing(function (array $data): array {
                        $data['creator_id'] = Auth::id();

                        return $data;
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('partners::filament/resources/partner/relation-managers/contacts.table.header-actions.create.notification.title'))
                            ->body(__('partners::filament/resources/partner/relation-managers/contacts.table.header-actions.create.notification.body')),
                    ),
            ]);
    }
}
