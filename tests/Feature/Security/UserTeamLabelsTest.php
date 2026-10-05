<?php

use Webkul\Security\Filament\Resources\TeamResource;
use Webkul\Security\Filament\Resources\UserResource;

it('uses arabic plural labels for security users resource', function (): void {
    app()->setLocale('ar');

    expect(UserResource::getNavigationLabel())->toBe('المستخدمون')
        ->and(UserResource::getPluralModelLabel())->toBe('المستخدمون')
        ->and(UserResource::getPluralModelLabel())->not->toContain('s')
        ->and(UserResource::getModelLabel())->toBe('المستخدم');
});

it('uses arabic plural labels for security teams resource', function (): void {
    app()->setLocale('ar');

    expect(TeamResource::getNavigationLabel())->toBe('الفرق')
        ->and(TeamResource::getPluralModelLabel())->toBe('الفرق')
        ->and(TeamResource::getPluralModelLabel())->not->toContain('s')
        ->and(TeamResource::getModelLabel())->toBe('الفريق');
});

it('loads security user and team overrides from the app layer', function (): void {
    expect((new ReflectionClass(UserResource::class))->getFileName())
        ->toContain('app/Overrides/Webkul/Security/Filament/Resources/UserResource.php')
        ->and((new ReflectionClass(TeamResource::class))->getFileName())
        ->toContain('app/Overrides/Webkul/Security/Filament/Resources/TeamResource.php');
});
