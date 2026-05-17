<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SetLocale;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Devonab\FilamentEasyFooter\EasyFooterPlugin;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Webkul\Manufacturing\ManufacturingPlugin;
use Webkul\Support\Filament\Pages\Profile;
use Webkul\Support\GlobalSearchProvider;
use Wezlo\FilamentApproval\FilamentApprovalPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->favicon(asset('images/favicon.ico'))
            ->brandLogo(asset('images/logo.png'))
            ->darkModeBrandLogo(asset('images/logo.png'))
            ->brandLogoHeight('2rem')
            ->passwordReset()
            ->emailVerification()
            ->profile()
            ->colors([
                'primary' => Color::Orange,
            ])
            ->unsavedChangesAlerts()
            ->topNavigation()
            ->maxContentWidth(Width::Full)
            ->databaseNotifications()
            ->userMenuItems([
                'profile' => Action::make('profile')
                    ->label(fn () => Auth::user()?->name)
                    ->url(fn (): string => Profile::getUrl()),
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label(__('admin.navigation.dashboard'))
                    ->icon('icon-dashboard'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.contact'))
                    ->icon('icon-contacts'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.sale'))
                    ->icon('icon-sales'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.purchase'))
                    ->icon('icon-purchases'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.manufacturing'))
                    ->icon('icon-manufacturing'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.inventory'))
                    ->icon('icon-inventories'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.invoice'))
                    ->icon('icon-invoices'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.accounting'))
                    ->icon('icon-accounting'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.project'))
                    ->icon('icon-projects'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.meetings'))
                    ->icon('heroicon-o-clipboard-document-list'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.correspondence'))
                    ->icon('heroicon-o-envelope'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.document-archive'))
                    ->icon('heroicon-o-archive-box'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.employee'))
                    ->icon('icon-employees'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.time-off'))
                    ->icon('icon-time-offs'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.recruitment'))
                    ->icon('icon-recruitments'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.website'))
                    ->icon('icon-website'),
                NavigationGroup::make()
                    ->label('Approvals')
                    ->icon('heroicon-o-check-badge'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.plugin'))
                    ->icon('icon-plugin'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.setting'))
                    ->icon('icon-settings'),
            ])
            ->plugins([
                FilamentApprovalPlugin::make()
                    ->navigationGroup('Approvals'),
                ManufacturingPlugin::make(),
                FilamentShieldPlugin::make()
                    ->gridColumns([
                        'default' => 1,
                        'sm'      => 1,
                        'lg'      => 2,
                        'xl'      => 3,
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm'      => 1,
                        'lg'      => 2,
                        'xl'      => 3,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                        'sm'      => 2,
                    ]),
                EasyFooterPlugin::make()
                    ->withSentence('Developed by NODHUM TECHNOLOGY · v'.config('app.version')),
            ])
            ->globalSearch(provider: GlobalSearchProvider::class)
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->multiFactorAuthentication([
                AppAuthentication::make()
                    ->recoverable(),
            ]);
    }
}
