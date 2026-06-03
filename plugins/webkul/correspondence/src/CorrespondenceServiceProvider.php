<?php

namespace Webkul\Correspondence;

use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Webkul\Correspondence\Console\NotifyOverdueCorrespondence;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\Correspondence\Policies\CorrespondencePolicy;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class CorrespondenceServiceProvider extends PackageServiceProvider
{
    public static string $name = 'correspondence';

    public static string $viewNamespace = 'correspondence';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([
                '2026_05_17_090000_create_correspondence_departments_table',
                '2026_05_17_090001_create_correspondences_table',
                '2026_05_17_090002_create_correspondence_attachments_table',
                '2026_05_17_090003_create_correspondence_followers_table',
                '2026_05_28_100000_add_employees_department_id_to_departments_table',
                '2026_05_28_100001_add_correspondence_id_to_projects_tasks_table',
                '2026_05_28_100002_create_correspondence_reads_table',
            ])
            ->hasDependencies([
                'projects',
                'meetings',
            ])
            ->runsMigrations()
            ->hasSeeder('Webkul\\Correspondence\\Database\\Seeders\\CorrespondenceApprovalFlowSeeder')
            ->hasSeeder('Webkul\\Correspondence\\Database\\Seeders\\CorrespondenceDepartmentSeeder')
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->installDependencies()
                    ->runsMigrations()
                    ->runsSeeders()
                    ->endWith(function (InstallCommand $command): void {
                        $command->call('shield:generate', [
                            '--resource' => 'CorrespondenceResource,DepartmentResource',
                            '--panel'    => 'admin',
                        ]);

                        $ar = require __DIR__.'/../resources/lang/ar/correspondence.php';
                        $en = require __DIR__.'/../resources/lang/en/correspondence.php';

                        $command->info($ar['install']['success'].' '.$en['install']['success']);
                    });
            })
            ->hasUninstallCommand(function (UninstallCommand $command): void {})
            ->icon('correspondence');
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(CorrespondencePlugin::make());
        });
    }

    public function packageBooted(): void
    {
        Gate::policy(Correspondence::class, CorrespondencePolicy::class);

        $this->commands([
            NotifyOverdueCorrespondence::class,
        ]);
    }
}
