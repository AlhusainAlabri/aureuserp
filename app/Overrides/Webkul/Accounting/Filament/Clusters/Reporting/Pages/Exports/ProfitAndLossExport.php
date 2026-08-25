<?php

namespace Webkul\Accounting\Filament\Clusters\Reporting\Pages\Exports;

use App\Support\Accounting\ReportingExportFormatter;
use App\Support\OmrFormatter;

class ProfitAndLossExport extends CoreProfitAndLossExport
{
    public function collection()
    {
        $rows = collect();

        $rows->push([null, __('accounting-extensions::reporting.profit-loss.heading', [
            'from' => OmrFormatter::localizedDate($this->dateFrom),
            'to'   => OmrFormatter::localizedDate($this->dateTo),
        ])]);
        $rows->push([null, null]);
        $rows->push([null, __('accounting-extensions::reporting.balance')]);
        $rows->push([null, null]);

        $rowIndex = 5;
        $metadata = [];

        foreach ($this->profitLossData['sections'] as $section) {
            $rows->push([$section['title'], '']);
            $metadata[$rowIndex++] = 'section_header';

            if (! empty($section['accounts'])) {
                foreach ($section['accounts'] as $account) {
                    $accountName = ($account['code'] ? $account['code'].' - ' : '').$account['name'];
                    $rows->push([
                        '            '.$accountName,
                        ReportingExportFormatter::amount($account['balance']),
                    ]);
                    $metadata[$rowIndex++] = 'account_line';
                }

                $rows->push([
                    $section['total_label'],
                    ReportingExportFormatter::amount($section['total']),
                ]);
                $metadata[$rowIndex++] = 'section_total';
            } else {
                $rows->push(['            '.$section['empty_message'], '']);
                $metadata[$rowIndex++] = 'empty_message';
            }

            $rows->push(['', '']);
            $rowIndex++;
        }

        $rows->push([
            $this->profitLossData['is_profit']
                ? __('accounting-extensions::reporting.net-profit')
                : __('accounting-extensions::reporting.net-loss'),
            ReportingExportFormatter::amount(abs($this->profitLossData['net_income'])),
        ]);
        $metadata[$rowIndex] = 'net_income';

        $this->rowMetadata = $metadata;

        return $rows;
    }
}
