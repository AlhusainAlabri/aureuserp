<?php

namespace App\Support\Dashboard;

use App\Filament\Assets\Concerns\InteractsWithAssetStats;
use App\Filament\Assets\Pages\AssetsDashboard;
use App\Filament\Assets\Pages\PendingBorrowingRequests;
use App\Filament\Inventory\Concerns\InteractsWithInventoryStockCounts;
use App\Filament\Inventory\Concerns\InteractsWithPendingReceiptCount;
use App\Filament\Inventory\Pages\InventoryDashboard;
use App\Models\Hr\EmployeeTraining;
use App\Services\Projects\UnifiedTaskQueryService;
use App\Support\FilamentUrl;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\Employee\Filament\Resources\SubmissionResource;
use Webkul\Employee\Models\EmployeeDocument;
use Webkul\Employee\Models\EmployeeSubmission;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource;
use Webkul\Purchase\Models\Order;
use Webkul\Recruitment\Filament\Clusters\Applications\Resources\ApplicantResource;
use Webkul\Recruitment\Models\Applicant;
use Webkul\Security\Models\User;
use Wezlo\FilamentApproval\Enums\ApprovalStatus;
use Wezlo\FilamentApproval\Models\Approval;

class OrgAlertCatalog
{
    use InteractsWithAssetStats;
    use InteractsWithInventoryStockCounts;
    use InteractsWithPendingReceiptCount;

    public const SCOPE_EXECUTIVE = 'executive';

    public const SCOPE_FINANCE = 'finance';

    public const SCOPE_HR = 'hr';

    public const SCOPE_MANAGER = 'manager';

    public const SCOPE_EMPLOYEE = 'employee';

    /**
     * @return list<string>
     */
    public static function scopesForUser(?User $user = null): array
    {
        $user ??= Auth::user();

        if (! $user) {
            return [self::SCOPE_EMPLOYEE];
        }

        $roles = $user->roles->pluck('name')->map(fn ($name): string => strtolower((string) $name));

        $scopes = [];

        if ($roles->intersect(['super_admin', 'general_manager', 'admin', 'admin_manager'])->isNotEmpty()) {
            $scopes[] = self::SCOPE_EXECUTIVE;
        }

        if ($roles->contains('finance_manager')) {
            $scopes[] = self::SCOPE_FINANCE;
        }

        if ($roles->contains('hr_manager')) {
            $scopes[] = self::SCOPE_HR;
        }

        if ($roles->intersect(['manager', 'department_manager'])->isNotEmpty()) {
            $scopes[] = self::SCOPE_MANAGER;
        }

        if ($scopes === []) {
            $scopes[] = self::SCOPE_EMPLOYEE;
        }

        return array_values(array_unique($scopes));
    }

    /**
     * @return Collection<int, array{id: string, module: string, label: string, severity: string, count: int, url: ?string}>
     */
    public static function alerts(?User $user = null): Collection
    {
        $user ??= Auth::user();
        $scopes = self::scopesForUser($user);

        /** @var list<array{id: string, module: string, label: string, severity: string, count: int, url: ?string}> $cached */
        $cached = DashboardMetricCache::remember('org_alerts', function () use ($scopes, $user): array {
            $instance = new self;

            return collect($instance->definitions())
                ->filter(fn (array $definition): bool => count(array_intersect($definition['scopes'], $scopes)) > 0)
                ->filter(fn (array $definition): bool => ($definition['visible'] ?? true)())
                ->map(function (array $definition) use ($user): array {
                    $count = (int) ($definition['count'])($user);
                    $url = ($definition['url'])($user);

                    return [
                        'id'       => $definition['id'],
                        'module'   => __($definition['module']),
                        'label'    => __($definition['label']),
                        'severity' => $definition['severity'],
                        'count'    => $count,
                        'url'      => filled($url) ? $url : null,
                    ];
                })
                ->filter(fn (array $alert): bool => $alert['count'] > 0)
                ->sort(function (array $a, array $b): int {
                    $severityRank = ['danger' => 0, 'warning' => 1, 'info' => 2];
                    $rankA = $severityRank[$a['severity']] ?? 3;
                    $rankB = $severityRank[$b['severity']] ?? 3;

                    if ($rankA !== $rankB) {
                        return $rankA <=> $rankB;
                    }

                    return $b['count'] <=> $a['count'];
                })
                ->values()
                ->all();
        });

        return collect($cached);
    }

    /**
     * @return list<array{id: string, module: string, label: string, severity: string, scopes: list<string>, visible: callable, count: callable, url: callable}>
     */
    protected function definitions(): array
    {
        return [
            [
                'id'       => 'pending_approvals',
                'module'   => 'dashboard.alerts.modules.approvals',
                'label'    => 'dashboard.alerts.items.pending_approvals',
                'severity' => 'danger',
                'scopes'   => [self::SCOPE_EXECUTIVE, self::SCOPE_FINANCE, self::SCOPE_MANAGER],
                'visible'  => fn (): bool => Schema::hasTable('approvals'),
                'count'    => fn (): int => Schema::hasTable('approvals')
                    ? (int) Approval::query()->where('status', ApprovalStatus::Pending)->count()
                    : 0,
                'url'      => fn (): ?string => DashboardNavigation::meetingApprovalsUrl()
                    ?? DashboardNavigation::correspondenceApprovalsUrl(),
            ],
            [
                'id'       => 'overdue_tasks',
                'module'   => 'dashboard.alerts.modules.projects',
                'label'    => 'dashboard.alerts.items.overdue_tasks',
                'severity' => 'danger',
                'scopes'   => [self::SCOPE_EXECUTIVE, self::SCOPE_MANAGER, self::SCOPE_EMPLOYEE],
                'visible'  => fn (): bool => UnifiedTaskQueryService::projectTasksAvailable()
                    || UnifiedTaskQueryService::meetingTasksAvailable(),
                'count'    => function (?User $user): int {
                    $scopes = self::scopesForUser($user);

                    if ($scopes === [self::SCOPE_EMPLOYEE]) {
                        return UnifiedTaskQueryService::countOverdueProjectTasks($user?->id);
                    }

                    $project = UnifiedTaskQueryService::countOverdueProjectTasks();
                    $meeting = UnifiedTaskQueryService::meetingTasksAvailable()
                        ? UnifiedTaskQueryService::overdueMeetingTasksQuery()->count()
                        : 0;

                    return $project + $meeting;
                },
                'url'      => fn (): ?string => DashboardNavigation::taskOperationsHubUrl()
                    ?? DashboardNavigation::projectTasksUrl(),
            ],
            [
                'id'       => 'missing_receipts',
                'module'   => 'dashboard.alerts.modules.purchases',
                'label'    => 'dashboard.alerts.items.missing_receipts',
                'severity' => 'warning',
                'scopes'   => [self::SCOPE_EXECUTIVE, self::SCOPE_FINANCE],
                'visible'  => fn (): bool => Schema::hasTable('purchases_orders')
                    && Schema::hasColumn('purchases_orders', 'receipt_uploaded'),
                'count'    => fn (): int => (int) Order::query()
                    ->whereIn('state', [OrderState::PURCHASE->value, OrderState::DONE->value])
                    ->where('receipt_uploaded', false)
                    ->count(),
                'url'      => fn (): ?string => class_exists(PurchaseOrderResource::class)
                    ? FilamentUrl::appendLocaleToUrl(PurchaseOrderResource::getUrl('index'))
                    : null,
            ],
            [
                'id'       => 'low_stock',
                'module'   => 'dashboard.alerts.modules.inventory',
                'label'    => 'dashboard.alerts.items.low_stock',
                'severity' => 'warning',
                'scopes'   => [self::SCOPE_EXECUTIVE, self::SCOPE_FINANCE, self::SCOPE_MANAGER],
                'visible'  => fn (): bool => Schema::hasTable('inventories_order_points'),
                'count'    => fn (): int => (new self)->countBelowMinimum(),
                'url'      => fn (): ?string => FilamentUrl::appendLocaleToUrl(InventoryDashboard::getUrl()),
            ],
            [
                'id'       => 'out_of_stock',
                'module'   => 'dashboard.alerts.modules.inventory',
                'label'    => 'dashboard.alerts.items.out_of_stock',
                'severity' => 'danger',
                'scopes'   => [self::SCOPE_EXECUTIVE, self::SCOPE_FINANCE, self::SCOPE_MANAGER],
                'visible'  => fn (): bool => Schema::hasTable('inventories_order_points'),
                'count'    => fn (): int => (new self)->countOutOfStock(),
                'url'      => fn (): ?string => FilamentUrl::appendLocaleToUrl(InventoryDashboard::getUrl()),
            ],
            [
                'id'       => 'expiring_documents',
                'module'   => 'dashboard.alerts.modules.hr',
                'label'    => 'dashboard.alerts.items.expiring_documents',
                'severity' => 'warning',
                'scopes'   => [self::SCOPE_EXECUTIVE, self::SCOPE_HR],
                'visible'  => fn (): bool => Schema::hasTable('employees_employee_documents'),
                'count'    => fn (): int => (int) EmployeeDocument::query()
                    ->whereNotNull('expiry_date')
                    ->whereBetween('expiry_date', [Carbon::today(), Carbon::today()->addDays(30)])
                    ->count(),
                'url'      => fn (): ?string => DashboardNavigation::expiringDocumentsUrl(),
            ],
            [
                'id'       => 'expired_documents',
                'module'   => 'dashboard.alerts.modules.hr',
                'label'    => 'dashboard.alerts.items.expired_documents',
                'severity' => 'danger',
                'scopes'   => [self::SCOPE_EXECUTIVE, self::SCOPE_HR],
                'visible'  => fn (): bool => Schema::hasTable('employees_employee_documents'),
                'count'    => fn (): int => (int) EmployeeDocument::query()
                    ->whereNotNull('expiry_date')
                    ->where('expiry_date', '<', Carbon::today())
                    ->count(),
                'url'      => fn (): ?string => DashboardNavigation::expiringDocumentsUrl(),
            ],
            [
                'id'       => 'expiring_certificates',
                'module'   => 'dashboard.alerts.modules.hr',
                'label'    => 'dashboard.alerts.items.expiring_certificates',
                'severity' => 'warning',
                'scopes'   => [self::SCOPE_EXECUTIVE, self::SCOPE_HR],
                'visible'  => fn (): bool => Schema::hasTable('employee_trainings'),
                'count'    => fn (): int => (int) EmployeeTraining::query()
                    ->whereNotNull('certificate_expiry_date')
                    ->whereBetween('certificate_expiry_date', [Carbon::today(), Carbon::today()->addDays(60)])
                    ->count(),
                'url'      => fn (): ?string => DashboardNavigation::expiringDocumentsUrl(),
            ],
            [
                'id'       => 'unread_correspondence',
                'module'   => 'dashboard.alerts.modules.correspondence',
                'label'    => 'dashboard.alerts.items.unread_correspondence',
                'severity' => 'warning',
                'scopes'   => [self::SCOPE_EXECUTIVE, self::SCOPE_MANAGER],
                'visible'  => fn (): bool => Schema::hasTable('correspondences'),
                'count'    => function (?User $user): int {
                    $query = Correspondence::query();

                    if (Schema::hasTable('correspondence_reads') && $user) {
                        return $query->unreadFor($user)->count();
                    }

                    return $query
                        ->where('direction', 'incoming')
                        ->where('status', 'received')
                        ->count();
                },
                'url'      => fn (): ?string => DashboardNavigation::correspondenceIndexUrl(),
            ],
            [
                'id'       => 'urgent_correspondence',
                'module'   => 'dashboard.alerts.modules.correspondence',
                'label'    => 'dashboard.alerts.items.urgent_correspondence',
                'severity' => 'danger',
                'scopes'   => [self::SCOPE_EXECUTIVE, self::SCOPE_MANAGER],
                'visible'  => fn (): bool => Schema::hasTable('correspondences'),
                'count'    => fn (): int => (int) Correspondence::query()
                    ->where('direction', 'incoming')
                    ->where('priority', 'urgent')
                    ->whereIn('status', ['received', 'draft'])
                    ->count(),
                'url'      => fn (): ?string => DashboardNavigation::correspondenceIndexUrl(),
            ],
            [
                'id'       => 'open_applicants',
                'module'   => 'dashboard.alerts.modules.recruitment',
                'label'    => 'dashboard.alerts.items.open_applicants',
                'severity' => 'info',
                'scopes'   => [self::SCOPE_EXECUTIVE, self::SCOPE_HR],
                'visible'  => fn (): bool => Schema::hasTable('recruitments_applicants'),
                'count'    => fn (): int => (int) Applicant::query()
                    ->where('is_active', true)
                    ->whereNull('date_closed')
                    ->count(),
                'url'      => fn (): ?string => class_exists(ApplicantResource::class)
                    ? FilamentUrl::appendLocaleToUrl(ApplicantResource::getUrl('index'))
                    : null,
            ],
            [
                'id'       => 'overdue_borrowings',
                'module'   => 'dashboard.alerts.modules.assets',
                'label'    => 'dashboard.alerts.items.overdue_borrowings',
                'severity' => 'danger',
                'scopes'   => [self::SCOPE_EXECUTIVE, self::SCOPE_MANAGER],
                'visible'  => fn (): bool => Schema::hasTable('asset_borrowings'),
                'count'    => fn (): int => (new self)->countOverdueBorrowings(),
                'url'      => fn (): ?string => FilamentUrl::appendLocaleToUrl(AssetsDashboard::getUrl()),
            ],
            [
                'id'       => 'pending_asset_requests',
                'module'   => 'dashboard.alerts.modules.assets',
                'label'    => 'dashboard.alerts.items.pending_asset_requests',
                'severity' => 'warning',
                'scopes'   => [self::SCOPE_EXECUTIVE, self::SCOPE_MANAGER],
                'visible'  => fn (): bool => Schema::hasTable('asset_borrowings'),
                'count'    => fn (): int => (new self)->countPendingRequests(),
                'url'      => fn (): ?string => FilamentUrl::appendLocaleToUrl(PendingBorrowingRequests::getUrl()),
            ],
            [
                'id'       => 'open_submissions',
                'module'   => 'dashboard.alerts.modules.submissions',
                'label'    => 'dashboard.alerts.items.open_submissions',
                'severity' => 'info',
                'scopes'   => [self::SCOPE_EXECUTIVE, self::SCOPE_HR],
                'visible'  => fn (): bool => Schema::hasTable('employees_employee_submissions'),
                'count'    => fn (): int => (int) EmployeeSubmission::query()
                    ->whereIn('status', ['open', 'under_review', 'pending'])
                    ->count(),
                'url'      => fn (): ?string => class_exists(SubmissionResource::class)
                    ? FilamentUrl::appendLocaleToUrl(SubmissionResource::getUrl('index'))
                    : null,
            ],
            [
                'id'       => 'pending_inventory_receipts',
                'module'   => 'dashboard.alerts.modules.inventory',
                'label'    => 'dashboard.alerts.items.pending_receipts',
                'severity' => 'warning',
                'scopes'   => [self::SCOPE_EXECUTIVE, self::SCOPE_FINANCE],
                'visible'  => fn (): bool => Schema::hasTable('inventories_order_points'),
                'count'    => fn (): int => (new self)->countPendingReceipts(),
                'url'      => fn (): ?string => (new self)->pendingReceiptsUrl(),
            ],
        ];
    }
}
