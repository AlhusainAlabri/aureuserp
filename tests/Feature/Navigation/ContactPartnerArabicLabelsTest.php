<?php

use App\Filament\Contacts\RelationManagers\ContactContactsRelationManager;
use Webkul\Contact\Filament\Resources\PartnerResource;

it('loads the contact partner resource override', function (): void {
    $reflection = new ReflectionClass(PartnerResource::class);

    expect($reflection->getFileName())->toContain('app/Overrides/Webkul/Contact/Filament/Resources/PartnerResource.php');
});

it('uses arabic relation labels on the contact partner resource', function (): void {
    app()->setLocale('ar');

    $relations = PartnerResource::getRelations();

    expect($relations)->toHaveCount(2)
        ->and($relations[0]->getLabel())->toBe(__('contacts::filament/resources/partner.relations.contacts'))
        ->and($relations[1]->getLabel())->toBe(__('contacts::filament/resources/partner.relations.addresses'));
});

it('localizes the contacts relation manager tab title in arabic', function (): void {
    app()->setLocale('ar');

    expect(ContactContactsRelationManager::getRelationshipTitle())
        ->toBe('جهات الاتصال');
});
