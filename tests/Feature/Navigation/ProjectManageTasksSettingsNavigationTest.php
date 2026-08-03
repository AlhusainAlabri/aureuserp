<?php

use Filament\Events\ServingFilament;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Illuminate\Support\Facades\Route;
use Webkul\PluginManager\Package;
use Webkul\Project\Filament\Clusters\Settings\Pages\ManageTasks as ProjectManageTasksSettings;

function projectManageTasksSettingsRouteIsRegistered(): bool
{
    if (! class_exists(ProjectManageTasksSettings::class)) {
        return false;
    }

    if (! Package::isPluginInstalled('projects')) {
        return false;
    }

    return Route::has(ProjectManageTasksSettings::getRouteName(Filament::getPanel('admin')));
}

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

it('does not fail when serving the admin panel without the projects plugin installed', function (): void {
    if (Package::isPluginInstalled('projects')) {
        $this->markTestSkipped('Projects plugin is installed.');
    }

    if (! class_exists(ProjectManageTasksSettings::class)) {
        $this->markTestSkipped('Projects plugin classes are unavailable.');
    }

    $panel = Filament::getPanel('admin');
    Filament::setCurrentPanel($panel);

    ServingFilament::dispatch();

    expect(true)->toBeTrue();
});

it('removes the project plugin settings shortcut from panel navigation items', function (): void {
    if (! projectManageTasksSettingsRouteIsRegistered()) {
        $this->markTestSkipped('Projects manage tasks settings route is unavailable.');
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
    if (! projectManageTasksSettingsRouteIsRegistered()) {
        $this->markTestSkipped('Projects manage tasks settings route is unavailable.');
    }

    expect(ProjectManageTasksSettings::getSlug(Filament::getPanel('admin')))->toBe('manage-tasks');
});
