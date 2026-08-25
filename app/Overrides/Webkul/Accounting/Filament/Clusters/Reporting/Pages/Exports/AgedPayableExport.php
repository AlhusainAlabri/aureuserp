<?php

namespace Webkul\Accounting\Filament\Clusters\Reporting\Pages\Exports;

use App\Support\Accounting\ReportingExportFormatter;
use App\Support\OmrFormatter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AgedPayableExport extends CoreAgedPayableExport
{
    public function headings(): array
    {
        return [
            [__('accounting-extensions::reporting.aged-payable.heading', [
                'date' => OmrFormatter::localizedDate($this->asOfDate),
            ])],
            [],
            [
                __('accounting-extensions::reporting.partner'),
                __('accounting-extensions::reporting.invoice-date'),
                __('accounting-extensions::reporting.at-date'),
                ReportingExportFormatter::periodBucket($this->period, 1),
                ReportingExportFormatter::periodBucket($this->period, 2),
                ReportingExportFormatter::periodBucket($this->period, 3),
                ReportingExportFormatter::periodBucket($this->period, 4),
                __('accounting-extensions::reporting.older'),
                __('accounting-extensions::reporting.total'),
            ],
        ];
    }

    public function array(): array
    {
        $rows = [];
        $rowIndex = 4;
        $metadata = [];

        $totals = collect(['at_date', 'period_1', 'period_2', 'period_3', 'period_4', 'older', 'total'])
            ->mapWithKeys(fn (string $key): array => [$key => 0])
            ->all();

        foreach ($this->partners as $partnerId => $partner) {
            $rows[] = [
                $partner['partner_name'],
                '',
                ...collect(['at_date', 'period_1', 'period_2', 'period_3', 'period_4', 'older'])
                    ->map(fn (string $key) => ReportingExportFormatter::numericCell($partner[$key]))
                    ->toArray(),
                ReportingExportFormatter::numericCell($partner['total']),
            ];
            $metadata[$rowIndex++] = 'partner_header';

            if (in_array($partnerId, $this->expandedPartners) && ! empty($partner['lines'])) {
                foreach ($partner['lines'] as $line) {
                    $rows[] = [
                        '        '.$line['move_name'],
                        OmrFormatter::localizedDate($line['invoice_date']),
                        ...collect(['at_date', 'period_1', 'period_2', 'period_3', 'period_4', 'older'])
                            ->map(fn (string $key) => ReportingExportFormatter::numericCell($line[$key]))
                            ->toArray(),
                        '',
                    ];
                    $metadata[$rowIndex++] = 'line';
                }
            }

            collect(['at_date', 'period_1', 'period_2', 'period_3', 'period_4', 'older', 'total'])
                ->each(fn (string $key) => $totals[$key] += $partner[$key]);
        }

        $rows[] = [
            __('accounting-extensions::reporting.total-aged-payable'),
            '',
            ...collect(['at_date', 'period_1', 'period_2', 'period_3', 'period_4', 'older'])
                ->map(fn (string $key) => ReportingExportFormatter::numericCell($totals[$key]))
                ->toArray(),
            ReportingExportFormatter::numericCell($totals['total']),
        ];
        $metadata[$rowIndex] = 'grand_total';

        $this->rowMetadata = $metadata;

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        parent::styles($sheet);

        $lastRow = count($this->rowMetadata) + 4;
        $sheet->getStyle("C4:I{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.000');

        return [];
    }
}
