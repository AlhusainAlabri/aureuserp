<?php

namespace Webkul\Accounting\Filament\Clusters\Reporting\Pages\Exports;

use App\Support\Accounting\ReportingExportFormatter;
use App\Support\OmrFormatter;

class BalanceSheetExport extends CoreBalanceSheetExport
{
    public function collection()
    {
        $rows = collect([
            [null, __('accounting-extensions::reporting.balance-sheet.heading', [
                'date' => OmrFormatter::localizedDate($this->date),
            ])],
            [null, null],
            [null, __('accounting-extensions::reporting.balance')],
            [null, null],
        ]);

        $rowIndex = 5;
        $metadata = [];

        foreach ($this->balanceSheetData['sections'] as $section) {
            $rows->push([$section['title'], '']);
            $metadata[$rowIndex++] = 'section_header';

            foreach ($section['subsections'] as $subsection) {
                $hasAccounts = ! empty($subsection['accounts']);
                $showSubsection = $hasAccounts || ! isset($subsection['show_if_empty']) || $subsection['show_if_empty'];

                if (! $showSubsection) {
                    continue;
                }

                $rows->push(['            '.$subsection['title'], '']);
                $metadata[$rowIndex++] = 'subsection_header';

                if ($hasAccounts) {
                    foreach ($subsection['accounts'] as $account) {
                        $accountName = ($account['code'] ? $account['code'].' - ' : '').$account['name'];
                        $rows->push([
                            '                        '.$accountName,
                            ReportingExportFormatter::amount($account['balance']),
                        ]);
                        $metadata[$rowIndex++] = 'account_line';
                    }

                    $rows->push([
                        '            '.$subsection['total_label'],
                        ReportingExportFormatter::amount($subsection['total']),
                    ]);
                    $metadata[$rowIndex++] = 'subsection_total';
                }
            }

            $rows->push([
                $section['total_label'],
                ReportingExportFormatter::amount($section['total']),
            ]);
            $metadata[$rowIndex++] = 'section_total';
            $rows->push(['', '']);
            $rowIndex++;
        }

        $rows->push([
            $this->balanceSheetData['grand_total_label'],
            ReportingExportFormatter::amount($this->balanceSheetData['grand_total']),
        ]);
        $metadata[$rowIndex] = 'grand_total';

        $this->rowMetadata = $metadata;

        return $rows;
    }
}
