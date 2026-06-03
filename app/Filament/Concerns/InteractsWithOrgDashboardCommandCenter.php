<?php

namespace App\Filament\Concerns;

use App\Filament\Widgets\Dashboard\Concerns\ResolvesOrgCommandCenterLeadHeading;
use App\Support\Dashboard\OrgAlertCatalog;
use Carbon\Carbon;

trait InteractsWithOrgDashboardCommandCenter
{
    use ResolvesOrgCommandCenterLeadHeading;

    /**
     * @return list<array{id: string, module: string, label: string, severity: string, count: int, url: ?string}>
     */
    public function getOrgDashboardAlerts(): array
    {
        return OrgAlertCatalog::alerts()->all();
    }

    public function getOrgDashboardTotalAlertCount(): int
    {
        return collect($this->getOrgDashboardAlerts())->sum('count');
    }

    public function getOrgDashboardLeadStatHeading(): ?string
    {
        $leadWidget = static::getCommandCenterStatWidgets()[0] ?? null;

        return $this->resolveLeadStatWidgetHeading($leadWidget);
    }

    public function getOrgDashboardFiltersButtonLabel(): string
    {
        $startDate = data_get($this->filters, 'startDate');
        $endDate = data_get($this->filters, 'endDate');

        if (filled($startDate) && filled($endDate)) {
            $start = Carbon::parse($startDate)->translatedFormat('d M');
            $end = Carbon::parse($endDate)->translatedFormat('d M Y');

            return "{$start} – {$end}";
        }

        return __('dashboard.filters.period');
    }

    /**
     * @return array{startDate: string, endDate: string}
     */
    protected function getDefaultDashboardFilters(): array
    {
        return [
            'startDate' => now()->subDays(30)->format('Y-m-d'),
            'endDate'   => now()->format('Y-m-d'),
        ];
    }

    protected function applyDefaultDashboardFilters(): void
    {
        $this->filters = $this->getDefaultDashboardFilters();

        if (method_exists($this, 'getFiltersForm')) {
            $this->getFiltersForm()->fill($this->filters);
        }

        if ($this->persistsFiltersInSession()) {
            session()->put($this->getFiltersSessionKey(), $this->filters);
        }
    }

    protected function dashboardFiltersNeedDefaults(): bool
    {
        return blank(data_get($this->filters, 'startDate'))
            || blank(data_get($this->filters, 'endDate'));
    }
}
