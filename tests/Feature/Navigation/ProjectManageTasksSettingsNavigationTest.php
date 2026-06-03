<?php

use Filament\Events\ServingFilament;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Webkul\Project\Filament\Clusters\Settings\Pages\ManageTasks as ProjectManageTasksSettings;

/**
 * @return array<int, string>
 */
function projectManageTasksSettingsPanelNavigationUrls(Panel $panel): array
{
    $reflection = new ReflectionClass($panel);
    $property = $reflection->getProperty('navigationItems');
    $property->setAccessible(true);

    return collect($property->getValue($panel))
        ->map(fn (NavigationItem $item): ?string => $item->getUrl())
        ->filter()
        ->values()
        ->all();
}

it('removes the project plugin settings shortcut from panel navigation items', function (): void {
    if (! class_exists(ProjectManageTasksSettings::class)) {
        $this->markTestSkipped('Projects plugin is not installed.');
    }

    $panel = Filament::getPanel('admin');
    $manageTasksUrl = ProjectManageTasksSettings::getUrl();

    Filament::setCurrentPanel($panel);
    ServingFilament::dispatch();

    expect(projectManageTasksSettingsPanelNavigationUrls($panel))
        ->not->toContain($manageTasksUrl);
});

it('keeps project manage tasks settings page registered for navigation in the settings cluster', function (): void {
    if (! class_exists(ProjectManageTasksSettings::class)) {
        $this->markTestSkipped('Projects plugin is not installed.');
    }

    expect(ProjectManageTasksSettings::shouldRegisterNavigation())->toBeTrue();
});

it('keeps the project manage tasks settings route reachable', function (): void {
    if (! class_exists(ProjectManageTasksSettings::class)) {
        $this->markTestSkipped('Projects plugin is not installed.');
    }

    expect(ProjectManageTasksSettings::getSlug(Filament::getPanel('admin')))->toBe('manage-tasks');
});
