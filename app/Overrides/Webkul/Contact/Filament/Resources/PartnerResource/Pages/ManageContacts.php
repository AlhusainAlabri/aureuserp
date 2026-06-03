<?php

namespace Webkul\Contact\Filament\Resources\PartnerResource\Pages;

use App\Filament\Contacts\RelationManagers\ContactContactsRelationManager;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Webkul\Contact\Filament\Resources\PartnerResource;
use Webkul\Partner\Filament\Resources\PartnerResource\Pages\ManageContacts as BaseManageContacts;

class ManageContacts extends BaseManageContacts
{
    protected static string $resource = PartnerResource::class;

    public static function getRelationshipTitle(): string
    {
        return __('contacts::filament/resources/partner.relations.contacts');
    }

    public function getTitle(): string|Htmlable
    {
        return __('contacts::filament/resources/partner.pages.manage-contacts.title', [
            'name' => $this->getRecordTitle(),
        ]);
    }

    public function table(Table $table): Table
    {
        return (new ContactContactsRelationManager)->table($table);
    }
}
