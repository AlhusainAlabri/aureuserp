<?php

namespace Webkul\Attendance;

use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Webkul\Attendance\Console\CloseDayCommand;
use Webkul\Attendance\Models\AttendanceDay;
use Webkul\Attendance\Models\OvertimeRequest;
use Webkul\Attendance\Policies\AttendanceDayPolicy;
use Webkul\Attendance\Policies\OvertimeRequestPolicy;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class AttendanceServiceProvider extends PackageServiceProvider
{
    public static string $name = 'attendances';

    public static string $viewNamespace = 'attendances';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([
                '2026_10_27_000001_create_attendance_import_batches_table',
                '2026_10_27_000002_create_attendance_punches_table',
                '2026_10_27_000003_create_attendance_days_table',
                '2026_10_27_000004_create_attendance_overtime_requests_table',
            ])
            ->hasDependencies([
                'employees',
            ])
            ->runsMigrations()
            ->hasSettings([
                '2026_10_27_000005_create_attendance_settings',
            ])
            ->runsSettings()
            ->hasCommands([
                CloseDayCommand::class,
            ])
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->installDependencies()
                    ->runsMigrations()
                    ->endWith(function (InstallCommand $command): void {
                        foreach (['AttendanceDayResource', 'OvertimeRequestResource'] as $resource) {
                            $command->call('shield:generate', [
                                '--resource' => $resource,
                                '--panel'    => 'admin',
                            ]);
                        }

                        $arPath = __DIR__.'/../resources/lang/ar/attendances.php';
                        $enPath = __DIR__.'/../resources/lang/en/attendances.php';

                        if (is_file($arPath) && is_file($enPath)) {
                            $ar = require $arPath;
                            $en = require $enPath;
                            $command->info(($ar['install']['success'] ?? '').' '.($en['install']['success'] ?? ''));
                        } else {
                            $command->info(__('attendances::attendances.install.success'));
                        }
                    });
            })
            ->hasUninstallCommand(function (UninstallCommand $command): void {})
            ->icon('attendance');
    }

    public function packageBooted(): void
    {
        Gate::policy(AttendanceDay::class, AttendanceDayPolicy::class);
        Gate::policy(OvertimeRequest::class, OvertimeRequestPolicy::class);
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(AttendancePlugin::make());
        });
    }
}
