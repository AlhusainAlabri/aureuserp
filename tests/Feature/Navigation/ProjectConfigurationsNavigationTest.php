<?php

use Filament\Pages\Page;
use Webkul\Project\Filament\Clusters\Configurations;
use Webkul\Project\Filament\Clusters\Configurations\Resources\TaskStageResource;
use Webkul\Project\Filament\Resources\ProjectResource;

it('keeps global page navigation registration enabled for projects', function (): void {
    $reflection = new ReflectionClass(Page::class);
    $property = $reflection->getProperty('shouldRegisterNavigation');
    $property->setAccessible(true);

    expect($property->getValue())->toBeTrue();
});

it('hides the project configurations cluster from main navigation', function (): void {
    expect(Configurations::shouldRegisterNavigation())->toBeFalse();
});

it('keeps the projects resource visible in main navigation', function (): void {
    expect(ProjectResource::shouldRegisterNavigation())->toBeTrue();
});

it('keeps task stages accessible via resource url', function (): void {
    expect(TaskStageResource::getSlug())->toBe('task-stages');
});
