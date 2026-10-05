<?php

use Webkul\Recruitment\Filament\Clusters\Applications\Resources\ApplicantResource;
use Webkul\Recruitment\Filament\Clusters\Applications\Resources\CandidateResource;
use Webkul\Recruitment\Filament\Clusters\Applications\Resources\JobByPositionResource;
use Webkul\Recruitment\Filament\Clusters\Configurations\Resources\JobPositionResource;

it('uses arabic plural labels for recruitment applicants resource', function (): void {
    app()->setLocale('ar');

    expect(ApplicantResource::getNavigationLabel())->toBe('المتقدمون')
        ->and(ApplicantResource::getPluralModelLabel())->toBe('المتقدمون')
        ->and(ApplicantResource::getPluralModelLabel())->not->toContain('s')
        ->and(ApplicantResource::getModelLabel())->toBe('المتقدم');
});

it('uses arabic plural labels for recruitment candidates resource', function (): void {
    app()->setLocale('ar');

    expect(CandidateResource::getNavigationLabel())->toBe('المرشحون')
        ->and(CandidateResource::getPluralModelLabel())->toBe('المرشحون')
        ->and(CandidateResource::getPluralModelLabel())->not->toContain('s')
        ->and(CandidateResource::getModelLabel())->toBe('المرشح');
});

it('uses arabic plural labels for recruitment job by position resource', function (): void {
    app()->setLocale('ar');

    expect(JobByPositionResource::getNavigationLabel())->toBe('المناصب الوظيفية')
        ->and(JobByPositionResource::getPluralModelLabel())->toBe('المناصب الوظيفية')
        ->and(JobByPositionResource::getPluralModelLabel())->not->toContain('s')
        ->and(JobByPositionResource::getModelLabel())->toBe('المنصب الوظيفي');
});

it('uses arabic plural labels for recruitment job positions resource', function (): void {
    app()->setLocale('ar');

    expect(JobPositionResource::getNavigationLabel())->toBe('المناصب الوظيفية')
        ->and(JobPositionResource::getPluralModelLabel())->toBe('المناصب الوظيفية')
        ->and(JobPositionResource::getPluralModelLabel())->not->toContain('s')
        ->and(JobPositionResource::getModelLabel())->toBe('المنصب الوظيفي')
        ->and(JobPositionResource::getNavigationGroup())->toBe('المناصب الوظيفية');
});

it('loads recruitment applicant override from the app layer', function (): void {
    expect((new ReflectionClass(ApplicantResource::class))->getFileName())
        ->toContain('app/Overrides/Webkul/Recruitment/Filament/Clusters/Applications/Resources/ApplicantResource.php');
});

it('loads recruitment candidate override from the app layer', function (): void {
    expect((new ReflectionClass(CandidateResource::class))->getFileName())
        ->toContain('app/Overrides/Webkul/Recruitment/Filament/Clusters/Applications/Resources/CandidateResource.php');
});

it('loads recruitment job position overrides from the app layer', function (): void {
    expect((new ReflectionClass(JobByPositionResource::class))->getFileName())
        ->toContain('app/Overrides/Webkul/Recruitment/Filament/Clusters/Applications/Resources/JobByPositionResource.php')
        ->and((new ReflectionClass(JobPositionResource::class))->getFileName())
        ->toContain('app/Overrides/Webkul/Recruitment/Filament/Clusters/Configurations/Resources/JobPositionResource.php');
});
