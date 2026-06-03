<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardFullWidthLayout;
use App\Support\Dashboard\OrgAlertCatalog;
use Filament\Widgets\Widget;

/**
 * Registered in dashboard widget lists for ordering; rendered on the page via
 * {@see Dashboard::content()} instead of the default widget grid.
 */
class OrgDashboardCommandCenterWidget extends Widget
{
    use HasOrgDashboardFullWidthLayout;

    protected static ?int $sort = 0;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected string $view = 'filament.widgets.org-dashboard-command-center-stub';

    /**
     * @return list<class-string<Widget>>
     */
    public function getStatWidgets(): array
    {
        return Dashboard::getCommandCenterStatWidgets();
    }

    /**
     * @return list<array{id: string, module: string, label: string, severity: string, count: int, url: ?string}>
     */
    public function getAlerts(): array
    {
        return OrgAlertCatalog::alerts()->all();
    }

    public function getTotalAlertCount(): int
    {
        return collect($this->getAlerts())->sum('count');
    }
}
