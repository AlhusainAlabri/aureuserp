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

it('resolves arabic partner field translations without partial override corruption', function (): void {
    app()->setLocale('ar');

    expect(__('partners::filament/resources/partner.form.sections.general.fields.tax-id'))
        ->toBe('الرقم الضريبي')
        ->and(__('partners::filament/resources/partner.infolist.tabs.sales-purchase.groups.sales'))
        ->toBe('المبيعات')
        ->and(__('partners::filament/resources/partner.table.groups.account-type'))
        ->toBe('نوع الحساب')
        ->and(__('partners::filament/resources/partner.form.sections.general.fields.name'))
        ->toBe('الاسم')
        ->and(__('partners::filament/resources/partner.infolist.sections.general.title'))
        ->toBe('عام');
});

it('loads contacts extension translations in arabic', function (): void {
    app()->setLocale('ar');

    expect(__('contacts-extensions::actions.back_to_contacts'))
        ->toBe('العودة إلى جهات الاتصال')
        ->and(__('contacts-extensions::placeholders.name-individual'))
        ->toBe('مثال: أحمد محمد');
});

it('resolves chatter action label in arabic', function (): void {
    app()->setLocale('ar');

    expect(__('chatter::filament/resources/actions/chatter-action.title'))
        ->toBe('المحادثات');
});
