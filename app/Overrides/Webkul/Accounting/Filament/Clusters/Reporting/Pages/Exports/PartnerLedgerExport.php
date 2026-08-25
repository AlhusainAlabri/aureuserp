<?php

namespace Webkul\Accounting\Filament\Clusters\Reporting\Pages\Exports;

use App\Support\Accounting\ReportingExportFormatter;
use App\Support\OmrFormatter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PartnerLedgerExport extends CorePartnerLedgerExport
{
    public function headings(): array
    {
        return [
            [__('accounting-extensions::reporting.partner-ledger.heading', [
                'from' => OmrFormatter::localizedDate($this->dateFrom),
                'to'   => OmrFormatter::localizedDate($this->dateTo),
            ])],
            [],
            [
                __('accounting-extensions::reporting.partner'),
                __('accounting-extensions::reporting.journal'),
                __('accounting-extensions::reporting.account'),
                __('accounting-extensions::reporting.invoice-date'),
                __('accounting-extensions::reporting.due-date'),
                __('accounting-extensions::reporting.debit'),
                __('accounting-extensions::reporting.credit'),
                __('accounting-extensions::reporting.balance'),
            ],
        ];
    }

    public function array(): array
    {
        $rows = [];
        $rowIndex = 4;
        $metadata = [];

        $totals = ['debit' => 0, 'credit' => 0];

        foreach ($this->partners as $partner) {
            $totals['debit'] += $partner['period_debit'];
            $totals['credit'] += $partner['period_credit'];

            $rows[] = [
                $partner['name'],
                '',
                '',
                '',
                '',
                ReportingExportFormatter::numericCell($partner['period_debit']),
                ReportingExportFormatter::numericCell($partner['period_credit']),
                ReportingExportFormatter::numericCell($partner['ending_balance']),
            ];
            $metadata[$rowIndex++] = 'partner_header';

            if (in_array($partner['id'], $this->expandedPartners)) {
                if ($partner['opening_balance'] != 0) {
                    $rows[] = [
                        '        '.__('accounting-extensions::reporting.opening-balance'),
                        OmrFormatter::localizedDate($this->dateFrom),
                        '',
                        '',
                        '',
                        $partner['opening_balance'] > 0 ? ReportingExportFormatter::numericCell($partner['opening_balance']) : '',
                        $partner['opening_balance'] < 0 ? ReportingExportFormatter::numericCell(abs($partner['opening_balance'])) : '',
                        ReportingExportFormatter::numericCell($partner['opening_balance']),
                    ];
                    $metadata[$rowIndex++] = 'opening_balance';
                }

                $runningBalance = $partner['opening_balance'];

                foreach (($this->getPartnerMovesCallback)($partner['id']) as $move) {
                    $runningBalance += $move['debit'] - $move['credit'];
                    $rows[] = [
                        '        '.$move['move_name'].($move['ref'] ? ' ('.$move['ref'].')' : ''),
                        $move['journal_name'] ?? '',
                        ($move['account_code'] ? $move['account_code'].' ' : '').$move['account_name'],
                        OmrFormatter::localizedDate($move['invoice_date']),
                        OmrFormatter::localizedDate($move['invoice_date_due']),
                        $move['debit'] > 0 ? ReportingExportFormatter::numericCell($move['debit']) : '',
                        $move['credit'] > 0 ? ReportingExportFormatter::numericCell($move['credit']) : '',
                        ReportingExportFormatter::numericCell($runningBalance),
                    ];
                    $metadata[$rowIndex++] = 'move_line';
                }
            }
        }

        $rows[] = [
            __('accounting-extensions::reporting.total-partner-ledger'),
            '',
            '',
            '',
            '',
            ReportingExportFormatter::numericCell($totals['debit']),
            ReportingExportFormatter::numericCell($totals['credit']),
            '',
        ];
        $metadata[$rowIndex] = 'grand_total';

        $this->rowMetadata = $metadata;

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        parent::styles($sheet);

        $lastRow = count($this->rowMetadata) + 4;
        $sheet->getStyle("F4:H{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.000');

        return [];
    }
}
