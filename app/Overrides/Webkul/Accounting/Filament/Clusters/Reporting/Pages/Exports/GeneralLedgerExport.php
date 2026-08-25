<?php

namespace Webkul\Accounting\Filament\Clusters\Reporting\Pages\Exports;

use App\Support\Accounting\ReportingExportFormatter;
use App\Support\OmrFormatter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GeneralLedgerExport extends CoreGeneralLedgerExport
{
    public function headings(): array
    {
        return [
            [__('accounting-extensions::reporting.general-ledger.heading', [
                'from' => OmrFormatter::localizedDate($this->dateFrom),
                'to'   => OmrFormatter::localizedDate($this->dateTo),
            ])],
            [],
            [
                __('accounting-extensions::reporting.account'),
                __('accounting-extensions::reporting.date'),
                __('accounting-extensions::reporting.communication'),
                __('accounting-extensions::reporting.partner'),
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

        foreach ($this->accounts as $account) {
            $totals['debit'] += $account->period_debit;
            $totals['credit'] += $account->period_credit;

            $rows[] = [
                $account->code.' '.$account->name,
                '',
                '',
                '',
                ReportingExportFormatter::numericCell($account->period_debit),
                ReportingExportFormatter::numericCell($account->period_credit),
                ReportingExportFormatter::numericCell($account->ending_balance),
            ];
            $metadata[$rowIndex++] = 'account_header';

            if (in_array($account->id, $this->expandedAccounts)) {
                if ($account->opening_balance != 0) {
                    $rows[] = [
                        '        '.__('accounting-extensions::reporting.opening-balance'),
                        OmrFormatter::localizedDate($this->dateFrom),
                        '',
                        '',
                        $account->opening_balance > 0 ? ReportingExportFormatter::numericCell($account->opening_balance) : '',
                        $account->opening_balance < 0 ? ReportingExportFormatter::numericCell(abs($account->opening_balance)) : '',
                        ReportingExportFormatter::numericCell($account->opening_balance),
                    ];
                    $metadata[$rowIndex++] = 'opening_balance';
                }

                $runningBalance = $account->opening_balance;

                foreach (($this->getAccountMovesCallback)($account->id) as $move) {
                    $runningBalance += $move['debit'] - $move['credit'];
                    $rows[] = [
                        '        '.$move['move_name'],
                        OmrFormatter::localizedDate($move['date']),
                        ($move['move_type'] ?? null) == 'entry' ? ($move['name'] ?? '') : '',
                        $move['partner_name'] ?? '',
                        $move['debit'] > 0 ? ReportingExportFormatter::numericCell($move['debit']) : '',
                        $move['credit'] > 0 ? ReportingExportFormatter::numericCell($move['credit']) : '',
                        ReportingExportFormatter::numericCell($runningBalance),
                    ];
                    $metadata[$rowIndex++] = 'move_line';
                }
            }
        }

        $rows[] = [
            __('accounting-extensions::reporting.total-general-ledger'),
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
        $sheet->getStyle("E4:G{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.000');

        return [];
    }
}
