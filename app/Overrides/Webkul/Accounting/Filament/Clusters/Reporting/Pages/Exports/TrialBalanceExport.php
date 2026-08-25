<?php

namespace Webkul\Accounting\Filament\Clusters\Reporting\Pages\Exports;

use App\Support\Accounting\ReportingExportFormatter;
use App\Support\OmrFormatter;

class TrialBalanceExport extends CoreTrialBalanceExport
{
    public function headings(): array
    {
        return [
            [__('accounting-extensions::reporting.trial-balance.heading', [
                'from' => OmrFormatter::localizedDate($this->dateFrom),
                'to'   => OmrFormatter::localizedDate($this->dateTo),
            ])],
            [],
            [
                '',
                __('accounting-extensions::reporting.initial-balance'),
                '',
                __('accounting-extensions::reporting.period-range', [
                    'from' => OmrFormatter::localizedDate($this->dateFrom, 'j M Y'),
                    'to'   => OmrFormatter::localizedDate($this->dateTo, 'j M Y'),
                ]),
                '',
                __('accounting-extensions::reporting.end-balance'),
                '',
            ],
            [
                __('accounting-extensions::reporting.account'),
                __('accounting-extensions::reporting.debit'),
                __('accounting-extensions::reporting.credit'),
                __('accounting-extensions::reporting.debit'),
                __('accounting-extensions::reporting.credit'),
                __('accounting-extensions::reporting.debit'),
                __('accounting-extensions::reporting.credit'),
            ],
        ];
    }

    public function array(): array
    {
        $rows = [];
        $rowIndex = 5;
        $metadata = [];

        collect($this->accounts)->each(function (array $account) use (&$rows, &$rowIndex, &$metadata): void {
            $rows[] = [
                '        '.($account['code'] ?? '').' '.($account['name'] ?? ''),
                ReportingExportFormatter::numericCell($account['initial_debit'] ?? 0),
                ReportingExportFormatter::numericCell($account['initial_credit'] ?? 0),
                ReportingExportFormatter::numericCell($account['period_debit'] ?? 0),
                ReportingExportFormatter::numericCell($account['period_credit'] ?? 0),
                ReportingExportFormatter::numericCell($account['end_debit'] ?? 0),
                ReportingExportFormatter::numericCell($account['end_credit'] ?? 0),
            ];
            $metadata[$rowIndex++] = 'account_line';
        });

        $rows[] = array_fill(0, 7, '');
        $rowIndex++;

        $rows[] = [
            __('accounting-extensions::reporting.total'),
            ReportingExportFormatter::numericCell($this->totals['initial_debit']),
            ReportingExportFormatter::numericCell($this->totals['initial_credit']),
            ReportingExportFormatter::numericCell($this->totals['period_debit']),
            ReportingExportFormatter::numericCell($this->totals['period_credit']),
            ReportingExportFormatter::numericCell($this->totals['end_debit']),
            ReportingExportFormatter::numericCell($this->totals['end_credit']),
        ];
        $metadata[$rowIndex] = 'grand_total';

        $this->rowMetadata = $metadata;

        return $rows;
    }
}
