<?php

use Webkul\Recruitment\Filament\Clusters\Configurations;
use Webkul\Recruitment\Filament\Clusters\Configurations\Resources\ActivityTypeResource;

it('uses arabic labels for recruitment activity types resource', function (): void {
    app()->setLocale('ar');

    expect(ActivityTypeResource::getNavigationLabel())->toBe('أنواع الأنشطة')
        ->and(ActivityTypeResource::getPluralModelLabel())->toBe('أنواع الأنشطة')
        ->and(ActivityTypeResource::getModelLabel())->toBe('نوع النشاط');
});

it('uses arabic breadcrumb for recruitment configurations cluster', function (): void {
    app()->setLocale('ar');

    expect(Configurations::getClusterBreadcrumb())->toBe('الإعدادات')
        ->and(Configurations::getNavigationLabel())->toBe('الإعدادات');
});

it('loads recruitment activity type overrides from the app layer', function (): void {
    expect((new ReflectionClass(ActivityTypeResource::class))->getFileName())
        ->toContain('app/Overrides/Webkul/Recruitment')
        ->and((new ReflectionClass(Configurations::class))->getFileName())
        ->toContain('app/Overrides/Webkul/Recruitment');
});
