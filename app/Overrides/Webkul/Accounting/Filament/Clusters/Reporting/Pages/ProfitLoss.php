<?php

namespace Webkul\Accounting\Filament\Clusters\Reporting\Pages;

use App\Filament\Concerns\LocalizesAccountingReportingPage;

class ProfitLoss extends CoreProfitLoss
{
    use LocalizesAccountingReportingPage;

    protected function getFormSchema(): array
    {
        return $this->getLocalizedReportingFilters('profit-loss');
    }
}
