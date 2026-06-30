<?php

namespace Webkul\Accounting\Filament\Clusters\Reporting\Pages;

use App\Filament\Concerns\LocalizesAccountingReportingPage;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Webkul\Account\Models\Journal;
use Webkul\Partner\Models\Partner;

class PartnerLedger extends CorePartnerLedger
{
    use LocalizesAccountingReportingPage;

    protected function getFormSchema(): array
    {
        return [
            Section::make()
                ->columns([
                    'default' => 1,
                    'sm'      => 3,
                ])
                ->schema([
                    $this->localizedDateRangeField(
                        __('accounting::filament/clusters/reporting.pages.partner-ledger.filters.date-range'),
                        fn () => $this->resetExpandedState(),
                    ),
                    Select::make('partners')
                        ->label(__('accounting::filament/clusters/reporting.pages.partner-ledger.filters.partners'))
                        ->multiple()
                        ->options(Partner::pluck('name', 'id'))
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(fn () => $this->resetExpandedState()),
                    Select::make('journals')
                        ->label(__('accounting::filament/clusters/reporting.pages.partner-ledger.filters.journals'))
                        ->multiple()
                        ->options(Journal::pluck('name', 'id'))
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(fn () => $this->resetExpandedState()),
                ])
                ->columnSpanFull(),
        ];
    }
}
