<?php

use Webkul\TimeOff\Filament\Clusters\Configurations;
use Webkul\TimeOff\Filament\Clusters\Configurations\Resources\ActivityTypeResource;
use Webkul\TimeOff\Filament\Clusters\Configurations\Resources\LeaveTypeResource;

it('uses arabic labels for time-off activity types resource', function (): void {
    app()->setLocale('ar');

    expect(ActivityTypeResource::getNavigationLabel())->toBe('أنواع الأنشطة')
        ->and(ActivityTypeResource::getPluralModelLabel())->toBe('أنواع الأنشطة')
        ->and(ActivityTypeResource::getModelLabel())->toBe('نوع النشاط');
});

it('uses arabic labels for time-off leave types resource', function (): void {
    app()->setLocale('ar');

    expect(LeaveTypeResource::getNavigationLabel())->toBe('أنواع الإجازات')
        ->and(LeaveTypeResource::getPluralModelLabel())->toBe('أنواع الإجازات')
        ->and(LeaveTypeResource::getPluralModelLabel())->not->toContain('s')
        ->and(LeaveTypeResource::getModelLabel())->toBe('نوع الإجازة');
});

it('uses arabic breadcrumb for time-off configurations cluster', function (): void {
    app()->setLocale('ar');

    expect(Configurations::getClusterBreadcrumb())->toBe('الإعدادات')
        ->and(Configurations::getNavigationLabel())->toBe('الإعدادات');
});

it('loads time-off activity type overrides from the app layer', function (): void {
    expect((new ReflectionClass(ActivityTypeResource::class))->getFileName())
        ->toContain('app/Overrides/Webkul/TimeOff')
        ->and((new ReflectionClass(Configurations::class))->getFileName())
        ->toContain('app/Overrides/Webkul/TimeOff');
});

it('loads time-off leave type override from the app layer', function (): void {
    expect((new ReflectionClass(LeaveTypeResource::class))->getFileName())
        ->toContain('app/Overrides/Webkul/TimeOff/Filament/Clusters/Configurations/Resources/LeaveTypeResource.php');
});
