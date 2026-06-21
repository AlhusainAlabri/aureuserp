<?php

use Filament\Facades\Filament;
use Filament\Pages\Page;
use Webkul\Contact\Filament\Clusters\Configurations;
use Webkul\Contact\Filament\Clusters\Configurations\Resources\TagResource;
use Webkul\Contact\Filament\Clusters\Configurations\Resources\TagResource\Pages\ManageTags;
use Webkul\Contact\Filament\Clusters\Configurations\Resources\TitleResource;
use Webkul\Contact\Filament\Resources\PartnerResource;

it('keeps global page navigation registration enabled', function (): void {
    $reflection = new ReflectionClass(Page::class);
    $property = $reflection->getProperty('shouldRegisterNavigation');
    $property->setAccessible(true);

    expect($property->getValue())->toBeTrue();
});

it('hides only the contact configurations cluster from main navigation', function (): void {
    expect(Configurations::shouldRegisterNavigation())->toBeFalse();
});

it('keeps the contacts resource visible in main navigation', function (): void {
    expect(PartnerResource::shouldRegisterNavigation())->toBeTrue();
});

it('shows breadcrumbs linking back to contacts on configuration pages', function (): void {
    if (! class_exists(ManageTags::class)) {
        $this->markTestSkipped('Contacts plugin is not installed.');
    }

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app()->setLocale('ar');

    $breadcrumbs = (new ManageTags)->getBreadcrumbs();

    $contactsUrl = PartnerResource::getUrl('index');
    $configurationsUrl = Configurations::getUrl();

    expect($breadcrumbs)
        ->toHaveKey($contactsUrl)
        ->toHaveKey($configurationsUrl)
        ->and(array_values($breadcrumbs))->toBe([
            __('contacts::filament/resources/partner.navigation.title'),
            __('contacts::filament/clusters/configurations.navigation.title'),
            (new ManageTags)->getTitle(),
        ]);
});

it('resolves configuration navigation and model labels from contacts translations', function (
    string $resourceClass,
    string $expectedNavigationEn,
    string $expectedNavigationAr,
    string $expectedModelEn,
    string $expectedModelAr,
): void {
    if (! class_exists($resourceClass)) {
        $this->markTestSkipped('Contacts plugin is not installed.');
    }

    app()->setLocale('en');

    expect($resourceClass::getNavigationLabel())->toBe($expectedNavigationEn)
        ->and($resourceClass::getModelLabel())->toBe($expectedModelEn);

    app()->setLocale('ar');

    expect($resourceClass::getNavigationLabel())->toBe($expectedNavigationAr)
        ->and($resourceClass::getModelLabel())->toBe($expectedModelAr);
})->with([
    [TagResource::class, 'Tags', 'الوسوم', 'Tag', 'وسم'],
    [TitleResource::class, 'Titles', 'الألقاب', 'Title', 'لقب'],
]);

it('uses arabic empty state headings on configuration tables', function (): void {
    if (! class_exists(ManageTags::class)) {
        $this->markTestSkipped('Contacts plugin is not installed.');
    }

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app()->setLocale('ar');

    $method = new ReflectionMethod(ManageTags::class, 'getTableEmptyStateHeading');
    $method->setAccessible(true);

    expect($method->invoke(new ManageTags))
        ->toBe(__('filament-tables::table.empty.heading', [
            'model' => __('contacts::filament/clusters/configurations/resources/tag.model.plural'),
        ]));
});
