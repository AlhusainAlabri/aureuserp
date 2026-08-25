<?php

namespace App\Providers;

use App\Http\Responses\LocaleAwareLoginResponse;
use App\Livewire\ModuleLauncherGlobalSearch;
use App\Support\Console\DestructiveCommandGuard;
use App\Support\ExtensionSchema;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\ImageEntry;
use Filament\Support\Facades\FilamentView;
use Filament\Support\View\ViewManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\View\PanelsRenderHook;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use ReflectionClass;
use Webkul\Security\Models\User;
use Wezlo\FilamentApproval\FilamentApprovalServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Authenticatable::class, User::class);
        $this->app->bind(LoginResponseContract::class, LocaleAwareLoginResponse::class);
    }

    public function boot(): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            app(DestructiveCommandGuard::class)->handleCommandStarting($event);
        });

        if (! app()->isProduction()) {
            ExtensionSchema::ensureAttachmentTables();
        }

        $this->registerFilamentApprovalBladeComponents();

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        config(['app.name' => brand_name()]);

        FileUpload::configureUsing(function (FileUpload $component): void {
            if ($component->getVisibility() === 'public') {
                $component->disk('public');
            }
        });

        ImageColumn::configureUsing(function (ImageColumn $column): void {
            if (str($column->getName())->contains('avatar')) {
                $column->disk('public');
            }
        });

        ImageEntry::configureUsing(function (ImageEntry $entry): void {
            if (str($entry->getName())->contains('avatar')) {
                $entry->disk('public');
            }
        });

        Filament::serving(function (): void {
            Select::configureUsing(function (Select $select): void {
                $select->searchPrompt(fn (): string => __('filament-forms::components.select.search_prompt'));
                $select->searchingMessage(fn (): string => __('filament-forms::components.select.searching_message'));
                $select->noSearchResultsMessage(fn (): string => __('filament-forms::components.select.no_search_results_message'));
                $select->preload(false);
            });

            DatePicker::configureUsing(function (DatePicker $picker): void {
                if (app()->getLocale() === 'ar') {
                    $picker->locale('ar')->displayFormat('j F Y');
                }
            });
        });

        $this->app->booted(function (): void {
            Livewire::component('module-launcher-global-search', ModuleLauncherGlobalSearch::class);

            FilamentView::resolved(function (ViewManager $viewManager): void {
                $this->removeSupportUserMenuVersionHook($viewManager);
            });
        });
    }

    protected function registerFilamentApprovalBladeComponents(): void
    {
        if (! class_exists(FilamentApprovalServiceProvider::class)) {
            return;
        }

        foreach ([
            'filament-approval::components.approval-status-badge',
            'filament-approval::components.approval-timeline',
        ] as $view) {
            Blade::component($view, $view);
        }
    }

    protected function removeSupportUserMenuVersionHook(ViewManager $viewManager): void
    {
        $reflection = new ReflectionClass($viewManager);
        $property = $reflection->getProperty('renderHooks');
        $property->setAccessible(true);

        $hooks = $property->getValue($viewManager);
        unset($hooks[PanelsRenderHook::USER_MENU_PROFILE_BEFORE]);
        $property->setValue($viewManager, $hooks);
    }
}
