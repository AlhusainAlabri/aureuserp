<?php

namespace App\Support\Pdf\Documents;

use App\Support\OmrFormatter;
use Webkul\Payroll\Models\EndOfServiceSettlement;

/**
 * Builds the printable end-of-service settlement statement as data for pdf.trade-document.
 */
class EndOfServiceSettlementDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function make(EndOfServiceSettlement $settlement): array
    {
        $settlement->loadMissing(['employee', 'company.currency']);

        $employee = $settlement->employee;
        $money = new DocumentMoney($settlement->company?->currency);
        $breakdown = $settlement->gratuity_breakdown ?? [];
        $reason = $breakdown['reason'] ?? null;

        $rows = collect($breakdown['rows'] ?? [])->map(fn (array $row): array => [
            'band'   => __('payroll::payroll.settlement.pdf.band', [
                'from' => static::trim($row['from']),
                'to'   => $row['to'] === null ? __('payroll::payroll.gratuity.open_ended') : static::trim($row['to']),
            ]),
            'years'  => number_format((float) $row['years'], 2),
            'days'   => number_format((float) $row['days'], 2),
            'amount' => $money->number($row['amount']),
        ])->all();

        $columns = $rows === [] ? [] : [
            ['key' => 'band', 'label' => __('payroll::payroll.settlement.pdf.columns.band')],
            ['key' => 'years', 'label' => __('payroll::payroll.settlement.pdf.columns.years'), 'width' => '16%', 'numeric' => true],
            ['key' => 'days', 'label' => __('payroll::payroll.settlement.pdf.columns.days'), 'width' => '18%', 'numeric' => true],
            ['key' => 'amount', 'label' => __('payroll::payroll.settlement.pdf.columns.amount'), 'width' => '22%', 'numeric' => true],
        ];

        $totals = array_values(array_filter([
            [__('payroll::payroll.settlement.fields.gratuity_amount'), $money->format($settlement->gratuity_amount), ''],
            [__('payroll::payroll.settlement.fields.leave_payout_amount').' ('.number_format((float) $settlement->unused_leave_days, 1).')', $money->format($settlement->leave_payout_amount), ''],
            [__('payroll::payroll.settlement.fields.final_salary_amount'), $money->format($settlement->final_salary_amount), ''],
            (float) $settlement->other_adjustment != 0.0
                ? [__('payroll::payroll.settlement.fields.other_adjustment'), $money->format($settlement->other_adjustment), '']
                : null,
            [__('payroll::payroll.settlement.fields.loan_offset_amount'), $money->format(-1 * (float) $settlement->loan_offset_amount), ''],
            [__('payroll::payroll.settlement.fields.net_amount'), $money->format($settlement->net_amount), 'grand'],
        ]));

        $notes = [];

        if (filled($reason)) {
            $notes[] = [
                'title' => __('payroll::payroll.settlement.fields.gratuity_breakdown'),
                'text'  => __('payroll::payroll.gratuity.reasons.'.$reason),
            ];
        }

        if (filled($settlement->adjustment_note)) {
            $notes[] = [
                'title' => __('payroll::payroll.settlement.fields.adjustment_note'),
                'text'  => $settlement->adjustment_note,
            ];
        }

        $years = (float) ($breakdown['service_years'] ?? 0);

        return [
            'company'    => $settlement->company,
            'title'      => __('payroll::payroll.settlement.pdf.title').' — '.$employee?->name,
            'partyLabel' => __('payroll::payroll.settlement.pdf.employee'),
            'party'      => null,
            'meta'       => array_values(array_filter([
                [__('payroll::payroll.settlement.pdf.employee'), (string) $employee?->name],
                filled($employee?->civil_id) ? [__('payroll::payroll.settlement.pdf.civil_id'), (string) $employee->civil_id] : null,
                [__('payroll::payroll.settlement.fields.join_date'), OmrFormatter::localizedDate($settlement->join_date)],
                [__('payroll::payroll.settlement.fields.departure_date'), OmrFormatter::localizedDate($settlement->departure_date)],
                [__('payroll::payroll.settlement.pdf.service'), __('payroll::payroll.settlement.pdf.service_value', [
                    'years' => number_format($years, 2),
                    'days'  => $settlement->service_days,
                ])],
                [__('payroll::payroll.settlement.fields.last_basic'), $money->format($settlement->last_basic)],
            ])),
            'columns'    => $columns,
            'rows'       => $rows,
            'totals'     => $totals,
            'notes'      => $notes,
            'closing'    => null,
        ];
    }

    protected static function trim(float|int|string $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }
}
