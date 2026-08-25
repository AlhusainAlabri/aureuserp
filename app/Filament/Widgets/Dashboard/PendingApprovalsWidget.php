<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\ConfiguresClickableStat;
use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardLayout;
use App\Support\Dashboard\DashboardMetricCache;
use App\Support\Dashboard\DashboardNavigation;
use App\Support\SchemaMemo;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Assets\Models\AssetBorrowing;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\Invoice\Models\Invoice;
use Webkul\Meetings\Models\Meeting;
use Webkul\Payroll\Models\PayrollBatch;
use Webkul\Purchase\Models\PurchaseOrder;
use Wezlo\FilamentApproval\Enums\ApprovalStatus;
use Wezlo\FilamentApproval\Models\Approval;

class PendingApprovalsWidget extends BaseWidget
{
    use ConfiguresClickableStat;
    use HasOrgDashboardLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected static bool $isLazy = true;

    protected ?string $pollingInterval = null;

    protected function getHeading(): ?string
    {
        return __('dashboard.widgets.pending_approvals');
    }

    protected function getStats(): array
    {
        try {
            if (! SchemaMemo::hasTable('approvals')) {
                return [
                    Stat::make(__('dashboard.widgets.pending_approvals'), __('dashboard.plugin_not_installed'))
                        ->color('gray'),
                ];
            }

            $counts = DashboardMetricCache::remember('pending_approvals', function (): array {
                $query = Approval::query()->where('status', ApprovalStatus::Pending);

                $meetingApprovals = self::countForModel($query, Meeting::class);
                $correspondenceApprovals = self::countForModel($query, Correspondence::class);
                $purchaseApprovals = self::countForModel($query, PurchaseOrder::class);
                $invoiceApprovals = self::countForModel($query, Invoice::class);
                $payrollApprovals = self::countForModel($query, PayrollBatch::class);
                $assetApprovals = self::countForModel($query, AssetBorrowing::class);

                $total = $meetingApprovals
                    + $correspondenceApprovals
                    + $purchaseApprovals
                    + $invoiceApprovals
                    + $payrollApprovals
                    + $assetApprovals;

                return [
                    'meeting'        => $meetingApprovals,
                    'correspondence' => $correspondenceApprovals,
                    'purchase'       => $purchaseApprovals,
                    'invoice'        => $invoiceApprovals,
                    'payroll'        => $payrollApprovals,
                    'asset'          => $assetApprovals,
                    'total'          => $total,
                ];
            });

            $total = $counts['total'];
            $color = $total > 0 ? 'danger' : 'success';

            return [
                $this->clickableStat(
                    label: __('dashboard.stats.meeting_approvals'),
                    value: $counts['meeting'],
                    url: DashboardNavigation::meetingApprovalsUrl(),
                    description: __('dashboard.stats.total_pending', ['count' => $total]),
                    descriptionIcon: 'heroicon-m-clipboard-document-check',
                    color: $color,
                    icon: 'heroicon-o-clipboard-document-check',
                ),

                $this->clickableStat(
                    label: __('dashboard.stats.correspondence_approvals'),
                    value: $counts['correspondence'],
                    url: DashboardNavigation::correspondenceApprovalsUrl(),
                    color: $counts['correspondence'] > 0 ? 'warning' : 'success',
                    icon: 'heroicon-o-envelope',
                ),

                $this->clickableStat(
                    label: __('dashboard.stats.purchase_approvals'),
                    value: $counts['purchase'],
                    url: DashboardNavigation::purchaseOrderApprovalsUrl(),
                    color: $counts['purchase'] > 0 ? 'warning' : 'success',
                    icon: 'heroicon-o-shopping-cart',
                ),

                $this->clickableStat(
                    label: __('dashboard.stats.invoice_approvals'),
                    value: $counts['invoice'],
                    url: DashboardNavigation::invoiceApprovalsUrl(),
                    color: $counts['invoice'] > 0 ? 'warning' : 'success',
                    icon: 'heroicon-o-document-text',
                ),

                $this->clickableStat(
                    label: __('dashboard.stats.payroll_approvals'),
                    value: $counts['payroll'] + $counts['asset'],
                    url: DashboardNavigation::payrollBatchApprovalsUrl()
                        ?? DashboardNavigation::assetBorrowingApprovalsUrl()
                        ?? DashboardNavigation::approvalFlowsUrl(),
                    color: ($counts['payroll'] + $counts['asset']) > 0 ? 'warning' : 'success',
                    icon: 'heroicon-o-banknotes',
                ),
            ];
        } catch (\Exception) {
            return [
                Stat::make(__('dashboard.widgets.pending_approvals'), __('dashboard.data_unavailable'))
                    ->color('gray'),
            ];
        }
    }

    /**
     * @param  class-string|null  $modelClass
     */
    protected static function countForModel(Builder $query, ?string $modelClass): int
    {
        if ($modelClass === null || ! class_exists($modelClass)) {
            return 0;
        }

        return (clone $query)
            ->where('approvable_type', (new $modelClass)->getMorphClass())
            ->count();
    }
}
