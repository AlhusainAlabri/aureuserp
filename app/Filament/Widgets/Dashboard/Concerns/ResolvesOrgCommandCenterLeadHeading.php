<?php

namespace App\Filament\Widgets\Dashboard\Concerns;

use App\Filament\Widgets\Dashboard\ExpiringCertificatesWidget;
use App\Filament\Widgets\Dashboard\ExpiringDocumentsWidget;
use App\Filament\Widgets\Dashboard\LowStockWidget;
use App\Filament\Widgets\Dashboard\MissingReceiptsWidget;
use App\Filament\Widgets\Dashboard\MyTasksTodayWidget;
use App\Filament\Widgets\Dashboard\OverdueTasksWidget;
use App\Filament\Widgets\Dashboard\PendingApprovalsWidget;
use App\Filament\Widgets\Dashboard\PendingLeaveRequestsWidget;

trait ResolvesOrgCommandCenterLeadHeading
{
    protected function resolveLeadStatWidgetHeading(?string $widgetClass): ?string
    {
        return match ($widgetClass) {
            PendingApprovalsWidget::class      => __('dashboard.widgets.pending_approvals'),
            OverdueTasksWidget::class          => __('dashboard.widgets.overdue_tasks'),
            LowStockWidget::class              => __('dashboard.widgets.low_stock'),
            MissingReceiptsWidget::class       => __('dashboard.widgets.missing_receipts'),
            ExpiringDocumentsWidget::class     => __('dashboard.widgets.expiring_documents'),
            ExpiringCertificatesWidget::class  => __('hr-extensions::widgets.expiring_certificates'),
            PendingLeaveRequestsWidget::class  => __('dashboard.widgets.leave_requests'),
            MyTasksTodayWidget::class          => __('dashboard.widgets.my_tasks'),
            default                            => null,
        };
    }
}
