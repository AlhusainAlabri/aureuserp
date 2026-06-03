<?php

namespace Webkul\MyNotes;

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Webkul\MyNotes\Console\Commands\SendNoteReminders;
use Webkul\MyNotes\Livewire\QuickNoteTopbar;
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
            ->hasRoute('web')
            ->hasMigrations([
                '2026_05_18_080000_create_notes_table',
                '2026_05_18_080001_create_note_checklist_items_table',
                '2026_05_18_080002_add_indexes_to_notes_table',
                '2026_06_02_100000_add_board_status_to_notes_table',
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
            ->hasUninstallCommand(function (UninstallCommand $_): void {})
            ->icon('my-notes');

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

        FilamentAsset::register([
            Css::make('my-notes-board', __DIR__.'/../resources/css/my-notes-board.css'),
        ]);

        $this->commands([
            SendNoteReminders::class,
        ]);

        Livewire::component('webkul.my-notes.quick-note-topbar', QuickNoteTopbar::class);

        Filament::serving(function (): void {
            FilamentView::registerRenderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): string => $this->renderQuickNoteTopbarHook(),
            );
        });
    }

    protected function renderQuickNoteTopbarHook(): string
    {
        if (! Schema::hasTable('notes') || ! Package::isPluginInstalled(static::$name)) {
            return '';
        }

        return view('my-notes::components.quick-note-topbar-mount')->render();
    }
}
