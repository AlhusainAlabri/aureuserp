<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardFullWidthLayout;
use Wezlo\FilamentApproval\Widgets\PendingApprovalsWidget as BasePendingApprovalsTableWidget;

class MyPendingApprovalsTableWidget extends BasePendingApprovalsTableWidget
{
    use HasOrgDashboardFullWidthLayout;

    protected static ?int $sort = 2;

    protected static bool $isLazy = true;

    protected ?string $pollingInterval = null;
}
