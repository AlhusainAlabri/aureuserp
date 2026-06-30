<?php

use App\Filament\Extensions\ProjectResourceExtensions;
use App\Filament\Extensions\TaskResourceExtensions;
use App\Filament\Projects\Resources\ProjectResource\Pages\ListProjects;
use Filament\Events\ServingFilament;
use Filament\Facades\Filament;
use Webkul\Project\Filament\Resources\ProjectResource;
use Webkul\Project\Filament\Resources\TaskResource;

it('uses arabic model labels for projects and tasks', function (): void {
    app()->setLocale('ar');

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    ServingFilament::dispatch();

    expect(ProjectResource::getModelLabel())->toBe('المشروع')
        ->and(ProjectResource::getPluralModelLabel())->toBe('المشاريع')
        ->and(TaskResource::getModelLabel())->toBe('المهمة')
        ->and(TaskResource::getPluralModelLabel())->toBe('المهام')
        ->and(ProjectResourceExtensions::getModelLabel())->toBe('المشروع')
        ->and(TaskResourceExtensions::getModelLabel())->toBe('المهمة');
});

it('loads project list page from the app layer', function (): void {
    expect((new ReflectionClass(ListProjects::class))->getFileName())
        ->toContain('app/Filament/Projects/Resources/ProjectResource/Pages/ListProjects.php');
});

it('translates project extension relation and empty state keys in arabic', function (): void {
    app()->setLocale('ar');

    expect(__('projects-extensions::project-relations.orders'))->toBe('أوامر الشراء')
        ->and(__('projects-extensions::empty.projects.heading'))->toBe('لا توجد مشاريع')
        ->and(__('projects-extensions::empty.timesheets.heading'))->toBe('لا توجد سجلات وقت')
        ->and(trans_choice('projects::filament/resources/project.table.actions.tasks', 1, ['count' => 1]))->toBe('1 مهمة')
        ->and(trans_choice('projects::filament/resources/project.table.actions.tasks', 3, ['count' => 3]))->toBe('3 مهام');
});
