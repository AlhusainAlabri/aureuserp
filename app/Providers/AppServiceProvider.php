<?php

namespace App\Providers;

use App\Http\Responses\LocaleAwareLoginResponse;
use App\Livewire\ModuleLauncherGlobalSearch;
use App\Policies\AccountTagPolicy;
use App\Policies\ApprovalFlowPolicy;
use App\Policies\CorrespondenceDepartmentPolicy;
use App\Policies\EmailLogPolicy;
use App\Policies\EmailTemplatePolicy;
use App\Policies\EmployeeComponentPolicy;
use App\Policies\EmployeeSubmissionPolicy;
use App\Policies\WarningTypePolicy;
use App\Services\Deploy\CommandRunner;
use App\Services\Deploy\SymfonyCommandRunner;
use App\Support\Branding;
use App\Support\Console\DestructiveCommandGuard;
use App\Support\DefaultCurrency;
use App\Support\ExtensionSchema;
use App\Support\Pdf\ArabicAwarePdf;
use App\Support\Rtl\LtrValueFormatter;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentView;
use Filament\Support\View\ViewManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use ReflectionClass;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Wezlo\FilamentApproval\FilamentApprovalServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DestructiveCommandGuard::class);
        $this->app->scoped(DefaultCurrency::class);

        $this->app->bind(Authenticatable::class, User::class);
        $this->app->singleton(CommandRunner::class, SymfonyCommandRunner::class);
        $this->app->bind(LoginResponseContract::class, LocaleAwareLoginResponse::class);

        // Dompdf has no Arabic shaping/bidi — shape glyphs for every PDF export app-wide.
        $this->app->bind('dompdf.wrapper', function ($app): ArabicAwarePdf {
            return new ArabicAwarePdf($app['dompdf'], $app['config'], $app['files'], $app['view']);
        });
    }

    public function boot(): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            app(DestructiveCommandGuard::class)->handleCommandStarting($event);
        });

        $this->registerShieldPolicies();

        Company::saved(fn () => Branding::forgetCache());
        Company::deleted(fn () => Branding::forgetCache());

        Event::listen(CommandFinished::class, function (CommandFinished $event): void {
            app(DestructiveCommandGuard::class)->handleCommandFinished($event);
        });

        if (! app()->isProduction()) {
            ExtensionSchema::ensureAttachmentTables();
        }

        $this->registerFilamentApprovalBladeComponents();

        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        config(['app.name' => brand_name()]);

        FileUpload::configureUsing(function (FileUpload $component): void {
            if ($component->getVisibility() === 'public') {
                $component->disk('public');
            }
        });

        Table::configureUsing(fn (Table $table) => $table->defaultCurrency(fn (): string => DefaultCurrency::code()));
        Schema::configureUsing(fn (Schema $schema) => $schema->defaultCurrency(fn (): string => DefaultCurrency::code()));

        TextEntry::configureUsing(function (TextEntry $entry): void {
            if (LtrValueFormatter::appliesTo($entry->getName())) {
                $entry->formatStateUsing(fn (mixed $state): mixed => LtrValueFormatter::format($state));
            }
        });

        TextColumn::configureUsing(function (TextColumn $column): void {
            if (LtrValueFormatter::appliesTo($column->getName())) {
                $column->formatStateUsing(fn (mixed $state): mixed => LtrValueFormatter::format($state));
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

    /**
     * Resources whose models had no policy, which left them open to every
     * signed-in user. They are now guarded by their Shield permissions.
     */
    protected function registerShieldPolicies(): void
    {
        $policies = [
            'Wezlo\\FilamentApproval\\Models\\ApprovalFlow'    => ApprovalFlowPolicy::class,
            'Webkul\\Payroll\\Models\\EmployeeComponent'       => EmployeeComponentPolicy::class,
            'Webkul\\Employee\\Models\\EmployeeSubmission'     => EmployeeSubmissionPolicy::class,
            'Webkul\\Employee\\Models\\WarningType'            => WarningTypePolicy::class,
            'Webkul\\Support\\Models\\EmailTemplate'           => EmailTemplatePolicy::class,
            'Webkul\\Support\\Models\\EmailLog'                => EmailLogPolicy::class,
            'Webkul\\Account\\Models\\Tag'                     => AccountTagPolicy::class,
            'Webkul\\Correspondence\\Models\\Department'       => CorrespondenceDepartmentPolicy::class,
        ];

        foreach ($policies as $model => $policy) {
            if (class_exists($model)) {
                Gate::policy($model, $policy);
            }
        }
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
