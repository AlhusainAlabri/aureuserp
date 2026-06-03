<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\InteractsWithAdvancedDashboard;
use App\Filament\Concerns\InteractsWithOrgDashboardCommandCenter;
use App\Filament\Widgets\Dashboard\ActiveLoansWidget;
use App\Filament\Widgets\Dashboard\ActiveProjectsWidget;
use App\Filament\Widgets\Dashboard\AssetsSummaryWidget;
use App\Filament\Widgets\Dashboard\CompletedProjectsWidget;
use App\Filament\Widgets\Dashboard\CorrespondenceVolumeChartWidget;
use App\Filament\Widgets\Dashboard\DeptSpendingChartWidget;
use App\Filament\Widgets\Dashboard\ExpiringCertificatesWidget;
use App\Filament\Widgets\Dashboard\ExpiringDocumentsWidget;
use App\Filament\Widgets\Dashboard\HeadcountWidget;
use App\Filament\Widgets\Dashboard\InventoryStockChartWidget;
use App\Filament\Widgets\Dashboard\InvoiceStatusWidget;
use App\Filament\Widgets\Dashboard\LowStockWidget;
use App\Filament\Widgets\Dashboard\MeetingsActivityChartWidget;
use App\Filament\Widgets\Dashboard\MissingReceiptsWidget;
use App\Filament\Widgets\Dashboard\MonthlyExpenseTrendWidget;
use App\Filament\Widgets\Dashboard\MyLeaveBalanceWidget;
use App\Filament\Widgets\Dashboard\MyNotesBoardWidget;
use App\Filament\Widgets\Dashboard\MyPayslipWidget;
use App\Filament\Widgets\Dashboard\MyTasksTodayWidget;
use App\Filament\Widgets\Dashboard\MyUpcomingMeetingsWidget;
use App\Filament\Widgets\Dashboard\OpenSubmissionsWidget;
use App\Filament\Widgets\Dashboard\OrgDashboardCommandCenterWidget;
use App\Filament\Widgets\Dashboard\OverdueTasksWidget;
use App\Filament\Widgets\Dashboard\PayrollSummaryWidget;
use App\Filament\Widgets\Dashboard\PendingApprovalsWidget;
use App\Filament\Widgets\Dashboard\PendingLeaveRequestsWidget;
use App\Filament\Widgets\Dashboard\PurchasesSpendChartWidget;
use App\Filament\Widgets\Dashboard\RecentRaisesWidget;
use App\Filament\Widgets\Dashboard\RecruitmentPipelineChartWidget;
use App\Filament\Widgets\Dashboard\RecruitmentPipelineWidget;
use App\Filament\Widgets\Dashboard\RevenueExpensesChartWidget;
use App\Filament\Widgets\Dashboard\TasksCompletionChartWidget;
use App\Filament\Widgets\Dashboard\UnreadCorrespondenceWidget;
use App\Filament\Widgets\Dashboard\UpcomingMeetingsWidget;
use App\Filament\Widgets\Dashboard\WarningsIssuedWidget;
use App\Support\Dashboard\DashboardMetricCache;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;

class Dashboard extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;
    use InteractsWithAdvancedDashboard;
    use InteractsWithOrgDashboardCommandCenter;

    protected static string $routePath = '/';

    protected static ?string $slug = 'home';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.org-dashboard';

    /**
     * @return array<int, array{label: string, description: string, url: string, icon: string, color: string}>
     */
    public function getDashboardHubLinks(): array
    {
        return [];
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.dashboard');
    }

    public function getTitle(): string
    {
        return __('dashboard.greeting', ['name' => auth()->user()?->name ?? '']);
    }

    public function getHeader(): ?View
    {
        return view('filament.components.org-dashboard-header', [
            'greeting' => $this->getTitle(),
            'overview' => __('dashboard.subheading'),
            'actions'  => $this->getCachedHeaderActions(),
        ]);
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    public function getFiltersSessionKey(): string
    {
        return md5($this::class).'_filters_v2';
    }

    public function booted(): void
    {
        if ($this->dashboardFiltersNeedDefaults()) {
            $this->applyDefaultDashboardFilters();
        }
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make([
                'default' => 1,
                'sm'      => 2,
            ])
                ->schema([
                    DatePicker::make('startDate')
                        ->label(__('dashboard.filters.start_date'))
                        ->default(now()->subDays(30)->format('Y-m-d'))
                        ->format('Y-m-d')
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Carbon::parse($state)->format('Y-m-d') : null)
                        ->maxDate(fn (Get $get) => $get('endDate') ?: now())
                        ->native(false),
                    DatePicker::make('endDate')
                        ->label(__('dashboard.filters.end_date'))
                        ->default(now()->format('Y-m-d'))
                        ->format('Y-m-d')
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Carbon::parse($state)->format('Y-m-d') : null)
                        ->minDate(fn (Get $get) => $get('startDate'))
                        ->maxDate(now())
                        ->native(false),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            SchemaView::make('filament.pages.org-dashboard-command-center'),
            $this->getWidgetsContentComponent(),
        ]);
    }

    public function getWidgetsContentComponent(): Component
    {
        return Grid::make($this->getColumns())
            ->schema(fn (): array => $this->getWidgetsSchemaComponents($this->getVisibleWidgetsWithoutCommandCenter()));
    }

    /**
     * @return array<class-string | WidgetConfiguration>
     */
    protected function getVisibleWidgetsWithoutCommandCenter(): array
    {
        return array_values(array_filter(
            $this->getVisibleWidgets(),
            fn (string|WidgetConfiguration $widget): bool => $this->resolveDashboardWidgetClass($widget) !== OrgDashboardCommandCenterWidget::class,
        ));
    }

    /**
     * @param  class-string | WidgetConfiguration  $widget
     * @return class-string
     */
    protected function resolveDashboardWidgetClass(string|WidgetConfiguration $widget): string
    {
        return $widget instanceof WidgetConfiguration ? $widget->widget : $widget;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label(__('dashboard.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->action(function (): void {
                    foreach (['pending_approvals', 'overdue_tasks', 'low_stock', 'expiring_documents', 'org_alerts'] as $key) {
                        DashboardMetricCache::forget($key);
                    }

                    $this->dispatch('$refresh');
                })
                ->color('gray'),
        ];
    }

    public function getWidgets(): array
    {
        $user = auth()->user();

        if (! $user) {
            return static::getEmployeeWidgets();
        }

        $roles = $user->roles->pluck('name')->map(fn ($n) => strtolower((string) $n));

        if ($roles->contains('super_admin') || $roles->contains('general_manager')) {
            return static::getGeneralManagerWidgets();
        }

        if ($roles->contains('admin') || $roles->contains('admin_manager')) {
            return static::getAdminWidgets();
        }

        if ($roles->contains('finance_manager')) {
            return static::getFinanceManagerWidgets();
        }

        if ($roles->contains('hr_manager')) {
            return static::getHrManagerWidgets();
        }

        if ($roles->contains('manager') || $roles->contains('department_manager')) {
            return static::getDepartmentManagerWidgets();
        }

        return static::getEmployeeWidgets();
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    /**
     * Stats shown beside smart alerts in the command-center layout (right column).
     *
     * @return list<class-string>
     */
    public static function getCommandCenterStatWidgets(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [MyTasksTodayWidget::class];
        }

        $roles = $user->roles->pluck('name')->map(fn ($name): string => strtolower((string) $name));

        if ($roles->intersect(['super_admin', 'general_manager', 'admin', 'admin_manager'])->isNotEmpty()) {
            return [
                PendingApprovalsWidget::class,
                OverdueTasksWidget::class,
                LowStockWidget::class,
            ];
        }

        if ($roles->contains('finance_manager')) {
            return [
                PendingApprovalsWidget::class,
                LowStockWidget::class,
                MissingReceiptsWidget::class,
            ];
        }

        if ($roles->contains('hr_manager')) {
            return [
                ExpiringDocumentsWidget::class,
                ExpiringCertificatesWidget::class,
                PendingLeaveRequestsWidget::class,
            ];
        }

        if ($roles->intersect(['manager', 'department_manager'])->isNotEmpty()) {
            return [
                PendingApprovalsWidget::class,
                OverdueTasksWidget::class,
                LowStockWidget::class,
            ];
        }

        return [MyTasksTodayWidget::class];
    }

    // ─── Widget sets per role ────────────────────────────────────────────────

    public static function getGeneralManagerWidgets(): array
    {
        return [
            OrgDashboardCommandCenterWidget::class,
            ExpiringDocumentsWidget::class,
            MissingReceiptsWidget::class,
            AssetsSummaryWidget::class,
            RevenueExpensesChartWidget::class,
            InvoiceStatusWidget::class,
            TasksCompletionChartWidget::class,
            MeetingsActivityChartWidget::class,
            CorrespondenceVolumeChartWidget::class,
            RecruitmentPipelineChartWidget::class,
            InventoryStockChartWidget::class,
            PurchasesSpendChartWidget::class,
            DeptSpendingChartWidget::class,
            HeadcountWidget::class,
            PayrollSummaryWidget::class,
            ActiveLoansWidget::class,
            UpcomingMeetingsWidget::class,
            UnreadCorrespondenceWidget::class,
            ActiveProjectsWidget::class,
            CompletedProjectsWidget::class,
            RecruitmentPipelineWidget::class,
            OpenSubmissionsWidget::class,
            WarningsIssuedWidget::class,
            MyNotesBoardWidget::class,
            RecentRaisesWidget::class,
        ];
    }

    public static function getFinanceManagerWidgets(): array
    {
        return [
            OrgDashboardCommandCenterWidget::class,
            RevenueExpensesChartWidget::class,
            InvoiceStatusWidget::class,
            PurchasesSpendChartWidget::class,
            DeptSpendingChartWidget::class,
            InventoryStockChartWidget::class,
            PayrollSummaryWidget::class,
            ActiveLoansWidget::class,
            MonthlyExpenseTrendWidget::class,
        ];
    }

    public static function getAdminWidgets(): array
    {
        return [
            OrgDashboardCommandCenterWidget::class,
            TasksCompletionChartWidget::class,
            MeetingsActivityChartWidget::class,
            CorrespondenceVolumeChartWidget::class,
            UpcomingMeetingsWidget::class,
            ActiveProjectsWidget::class,
            UnreadCorrespondenceWidget::class,
            MyNotesBoardWidget::class,
        ];
    }

    public static function getHrManagerWidgets(): array
    {
        return [
            OrgDashboardCommandCenterWidget::class,
            RecruitmentPipelineChartWidget::class,
            HeadcountWidget::class,
            RecentRaisesWidget::class,
            PayrollSummaryWidget::class,
            ActiveLoansWidget::class,
            RecruitmentPipelineWidget::class,
            WarningsIssuedWidget::class,
            OpenSubmissionsWidget::class,
            MyNotesBoardWidget::class,
        ];
    }

    public static function getDepartmentManagerWidgets(): array
    {
        return [
            OrgDashboardCommandCenterWidget::class,
            UpcomingMeetingsWidget::class,
            TasksCompletionChartWidget::class,
            MeetingsActivityChartWidget::class,
            AssetsSummaryWidget::class,
            ActiveProjectsWidget::class,
            CompletedProjectsWidget::class,
            UnreadCorrespondenceWidget::class,
        ];
    }

    public static function getEmployeeWidgets(): array
    {
        return [
            OrgDashboardCommandCenterWidget::class,
            MyUpcomingMeetingsWidget::class,
            MyLeaveBalanceWidget::class,
            MyPayslipWidget::class,
            MyNotesBoardWidget::class,
        ];
    }
}
