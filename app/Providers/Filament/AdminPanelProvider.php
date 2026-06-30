<?php

namespace App\Providers\Filament;

use App\Filament\Assets\Pages\AssetsDashboard;
use App\Filament\Assets\Pages\MyBorrowedAssets;
use App\Filament\Assets\Pages\MyBorrowingRequests;
use App\Filament\Assets\Pages\PendingBorrowingRequests;
use App\Filament\Assets\Resources\AssetBorrowingResource;
use App\Filament\Inventory\Pages\InventoryDashboard;
use App\Filament\Inventory\Pages\MovementReportArchivesPage;
use App\Filament\Inventory\Pages\MovementReportPage;
use App\Filament\Inventory\Pages\ProductPurchaseHistoryPage;
use App\Filament\Inventory\Pages\RecordConsumption;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\InternalRequests;
use App\Filament\Pages\ModuleLauncher;
use App\Filament\Pages\MyEmployeeProfile;
use App\Filament\Pages\MyEmployeeSubmissions;
use App\Filament\Pages\MyRequests;
use App\Filament\Pages\MySelfAssessment;
use App\Filament\Pages\MyWarnings;
use App\Filament\Projects\Pages\OperationsCalendar;
use App\Filament\Projects\Pages\TaskKanban;
use App\Filament\Projects\Pages\TaskOperationsHub;
use App\Filament\Resources\DashboardShortcutResource;
use App\Filament\Widgets\Dashboard\ActiveLoansWidget;
use App\Filament\Widgets\Dashboard\ActiveProjectsWidget;
use App\Filament\Widgets\Dashboard\AssetsSummaryWidget;
use App\Filament\Widgets\Dashboard\DeptSpendingChartWidget;
use App\Filament\Widgets\Dashboard\ExpiringCertificatesWidget;
use App\Filament\Widgets\Dashboard\ExpiringDocumentsWidget;
use App\Filament\Widgets\Dashboard\HeadcountWidget;
use App\Filament\Widgets\Dashboard\InvoiceStatusWidget;
use App\Filament\Widgets\Dashboard\LowStockWidget;
use App\Filament\Widgets\Dashboard\MissingReceiptsWidget;
use App\Filament\Widgets\Dashboard\MonthlyExpenseTrendWidget;
use App\Filament\Widgets\Dashboard\MyLeaveBalanceWidget;
use App\Filament\Widgets\Dashboard\MyPayslipWidget;
use App\Filament\Widgets\Dashboard\MyTasksTodayWidget;
use App\Filament\Widgets\Dashboard\MyUpcomingMeetingsWidget;
use App\Filament\Widgets\Dashboard\OpenSubmissionsWidget;
use App\Filament\Widgets\Dashboard\OverdueTasksWidget;
use App\Filament\Widgets\Dashboard\PayrollSummaryWidget;
use App\Filament\Widgets\Dashboard\PendingApprovalsWidget;
use App\Filament\Widgets\Dashboard\PendingLeaveRequestsWidget;
use App\Filament\Widgets\Dashboard\RecentRaisesWidget;
use App\Filament\Widgets\Dashboard\RecruitmentPipelineWidget;
use App\Filament\Widgets\Dashboard\RevenueExpensesChartWidget;
use App\Filament\Widgets\Dashboard\UnreadCorrespondenceWidget;
use App\Filament\Widgets\Dashboard\UpcomingMeetingsWidget;
use App\Filament\Widgets\Dashboard\UpcomingRemindersWidget;
use App\Filament\Widgets\Dashboard\WarningsIssuedWidget;
use App\Http\Middleware\CheckEmployeeFileClosure;
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
use Filament\View\PanelsRenderHook;
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
            ->homeUrl(fn (): string => ModuleLauncher::getUrl())
            ->login()
            ->favicon(asset('images/favicon.ico'))
            ->brandLogo(fn (): string => filament()->auth()->check()
                ? asset('images/logo.png')
                : asset('images/logo_2.png'))
            ->darkModeBrandLogo(fn (): string => filament()->auth()->check()
                ? asset('images/logo.png')
                : asset('images/logo_2.png'))
            ->brandLogoHeight(fn (): string => filament()->auth()->check() ? '2rem' : '4rem')
            ->passwordReset()
            ->emailVerification()
            ->profile()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                'primary' => Color::Green,
                'danger'  => Color::Rose,
                'gray'    => Color::Gray,
                'info'    => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
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
                    ->label(fn (): string => __('admin.navigation.dashboard'))
                    ->icon('icon-dashboard'),
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.contact'))
                    ->icon('icon-contacts'),
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.sale'))
                    ->icon('icon-sales'),
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.purchase'))
                    ->icon('icon-purchases'),
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.manufacturing'))
                    ->icon('icon-manufacturing'),
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.inventory'))
                    ->icon('icon-inventories'),
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.invoice'))
                    ->icon('icon-invoices'),
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.accounting'))
                    ->icon('icon-accounting'),
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.project'))
                    ->icon('icon-projects'),
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.meetings'))
                    ->icon('icon-meetings'),
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.my-notes'))
                    ->icon('icon-my-notes'),
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.correspondence'))
                    ->icon('icon-correspondence'),
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.document-archive'))
                    ->icon('icon-document-archive'),
                NavigationGroup::make()
                    ->label(fn (): string => __('assets::assets.navigation.group'))
                    ->icon('icon-assets'),
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.employee'))
                    ->icon('icon-employees'),
                NavigationGroup::make()
                    ->label(fn (): string => __('payroll::payroll.navigation.group'))
                    ->icon('icon-payroll'),
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.time-off'))
                    ->icon('icon-time-offs'),
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.recruitment'))
                    ->icon('icon-recruitments'),
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.website'))
                    ->icon('icon-website'),
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.approvals'))
                    ->icon('icon-approvals'),
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.plugin'))
                    ->icon('icon-plugin'),
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.setting'))
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
                    ->withSentence(__('admin.footer.sentence', ['version' => config('app.version')])),
            ])
            ->resources([
                AssetBorrowingResource::class,
                DashboardShortcutResource::class,
            ])
            ->pages([
                ModuleLauncher::class,
                Dashboard::class,
                MyRequests::class,
                InternalRequests::class,
                MyEmployeeProfile::class,
                MyEmployeeSubmissions::class,
                MySelfAssessment::class,
                MyWarnings::class,
                AssetsDashboard::class,
                MyBorrowingRequests::class,
                PendingBorrowingRequests::class,
                MyBorrowedAssets::class,
                InventoryDashboard::class,
                TaskOperationsHub::class,
                TaskKanban::class,
                OperationsCalendar::class,
                MovementReportPage::class,
                MovementReportArchivesPage::class,
                RecordConsumption::class,
                ProductPurchaseHistoryPage::class,
            ])
            ->widgets([
                PendingApprovalsWidget::class,
                OverdueTasksWidget::class,
                LowStockWidget::class,
                AssetsSummaryWidget::class,
                MissingReceiptsWidget::class,
                ExpiringDocumentsWidget::class,
                ExpiringCertificatesWidget::class,
                RecentRaisesWidget::class,
                RevenueExpensesChartWidget::class,
                InvoiceStatusWidget::class,
                PayrollSummaryWidget::class,
                ActiveLoansWidget::class,
                MyPayslipWidget::class,
                MonthlyExpenseTrendWidget::class,
                DeptSpendingChartWidget::class,
                UpcomingMeetingsWidget::class,
                UnreadCorrespondenceWidget::class,
                ActiveProjectsWidget::class,
                HeadcountWidget::class,
                RecruitmentPipelineWidget::class,
                WarningsIssuedWidget::class,
                OpenSubmissionsWidget::class,
                PendingLeaveRequestsWidget::class,
                MyTasksTodayWidget::class,
                MyUpcomingMeetingsWidget::class,
                MyLeaveBalanceWidget::class,
                UpcomingRemindersWidget::class,
            ])
            ->renderHook(
                PanelsRenderHook::SIMPLE_LAYOUT_START,
                fn () => view('filament.components.auth-language-switcher'),
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn () => view('filament.components.quick-create'),
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_END,
                fn () => view('filament.components.language-switcher'),
            )
            ->globalSearch(provider: GlobalSearchProvider::class)
            ->spa()
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
                CheckEmployeeFileClosure::class,
            ])
            ->multiFactorAuthentication([
                AppAuthentication::make()
                    ->recoverable(),
            ]);
    }
}
