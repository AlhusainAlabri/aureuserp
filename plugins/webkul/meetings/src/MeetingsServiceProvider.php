<?php

namespace Webkul\Meetings;

use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Webkul\Meetings\Console\NotifyOverdueMeetingTasks;
use Webkul\Meetings\Models\Meeting;
use Webkul\Meetings\Policies\MeetingPolicy;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class MeetingsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'meetings';

    public static string $viewNamespace = 'meetings';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([
                '2026_05_17_080000_create_meetings_table',
                '2026_05_17_080001_create_meeting_attendees_table',
                '2026_05_17_080002_create_meeting_tasks_table',
                '2026_05_17_080003_create_meeting_attachments_table',
            ])
            ->hasDependencies([
                'projects',
                'employees',
            ])
            ->runsMigrations()
            ->hasSeeder('Webkul\\Meetings\\Database\\Seeders\\MeetingApprovalFlowSeeder')
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->installDependencies()
                    ->runsMigrations()
                    ->runsSeeders()
                    ->endWith(function (InstallCommand $command): void {
                        $command->call('shield:generate', [
                            '--resource' => 'MeetingResource',
                            '--panel'    => 'admin',
                        ]);

                        $ar = require __DIR__.'/../resources/lang/ar/meetings.php';
                        $en = require __DIR__.'/../resources/lang/en/meetings.php';

                        $command->info($ar['install']['success'].' '.$en['install']['success']);
                    });
            })
            ->hasUninstallCommand(function (UninstallCommand $command): void {})
            ->icon('meetings');
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(MeetingsPlugin::make());
        });
    }

    public function packageBooted(): void
    {
        Gate::policy(Meeting::class, MeetingPolicy::class);

        $this->commands([
            NotifyOverdueMeetingTasks::class,
        ]);
    }
}
