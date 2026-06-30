<?php

namespace App\Filament\Concerns;

use App\Filament\Extensions\AccountingResourceExtensions;
use Carbon\Carbon;
use Filament\Schemas\Components\Component;

trait LocalizesAccountingReportingPage
{
    use LocalizesAccountingReporting;

    /**
     * @return array<int, Component>
     */
    protected function getLocalizedReportingFilters(string $pageKey): array
    {
        return $this->localizedReportingFilterSchema(
            __("accounting::filament/clusters/reporting.pages.{$pageKey}.filters.date-range"),
            __("accounting::filament/clusters/reporting.pages.{$pageKey}.filters.journals"),
        );
    }

    /**
     * @return array<string, array{0: Carbon, 1: Carbon}>
     */
    protected function localizedReportingDateRanges(): array
    {
        return AccountingResourceExtensions::localizedDateRanges();
    }
}
