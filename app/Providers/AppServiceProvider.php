<?php

namespace App\Providers;

use App\Livewire\ModuleLauncherGlobalSearch;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\ImageEntry;
use Filament\Support\Facades\FilamentView;
use Filament\Support\View\ViewManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use ReflectionClass;
use Webkul\Security\Models\User;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Authenticatable::class, User::class);
    }

    public function boot(): void
    {
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
