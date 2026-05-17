<?php

namespace Webkul\MyNotes;

use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Webkul\MyNotes\Console\Commands\SendNoteReminders;
use Webkul\MyNotes\Models\Note;
use Webkul\MyNotes\Policies\NotePolicy;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class MyNotesServiceProvider extends PackageServiceProvider
{
    public static string $name = 'my-notes';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([
                '2026_05_18_080000_create_notes_table',
                '2026_05_18_080001_create_note_checklist_items_table',
            ])
            ->runsMigrations()
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->runsMigrations()
                    ->endWith(function (InstallCommand $command): void {
                        $ar = require __DIR__.'/../resources/lang/ar/notes.php';
                        $en = require __DIR__.'/../resources/lang/en/notes.php';

                        $command->info($ar['install']['success'].' '.$en['install']['success']);
                    });
            })
            ->hasUninstallCommand(function (UninstallCommand $command): void {})
            ->icon('my-notes');

        $package->commands = [
            SendNoteReminders::class,
        ];
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(MyNotesPlugin::make());
        });
    }

    public function packageBooted(): void
    {
        Gate::policy(Note::class, NotePolicy::class);

        $this->commands([
            SendNoteReminders::class,
        ]);
    }
}
