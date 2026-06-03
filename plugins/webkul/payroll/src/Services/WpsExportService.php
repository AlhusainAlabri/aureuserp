<?php

namespace Webkul\Payroll\Services;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\Payroll\Enums\PaymentMethod;
use Webkul\Payroll\Models\PayrollBatch;

class WpsExportService
{
    /**
     * @return StreamedResponse|Response
     */
    public function export(PayrollBatch $batch)
    {
        $batch->loadMissing(['payslips.employee', 'company']);

        $payslips = $batch->payslips
            ->filter(fn ($payslip): bool => $payslip->payment_method === PaymentMethod::BankTransfer
                && filled($payslip->bank_account_number));

        $periodLabel = sprintf('%04d%02d', $batch->period_year, $batch->period_month);
        $companySlug = str($batch->company?->name ?? 'company')->slug();
        $filename = sprintf('WPS_%s_%s.csv', $companySlug, $periodLabel);
        $directory = sprintf('payroll/wps/%d', $batch->period_year);
        $path = $directory.'/'.$filename;

        $csv = $this->buildCsv($batch, $payslips);
        Storage::disk('private')->put($path, $csv);

        return response()->download(
            Storage::disk('private')->path($path),
            $filename,
            ['Content-Type' => 'text/csv'],
        );
    }

    protected function buildCsv(PayrollBatch $batch, $payslips): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, [
            $batch->company?->name,
            $batch->company?->registration_number,
            sprintf('%02d/%d', $batch->period_month, $batch->period_year),
            number_format((float) $batch->total_net, 3, '.', ''),
        ]);

        fputcsv($handle, [
            'Employee ID',
            'Full Name',
            'Civil ID',
            'Bank Code',
            'Account Number',
            'Branch Code',
            'IBAN',
            'Basic Salary',
            'Allowances',
            'Deductions',
            'Net Salary',
            'Currency',
            'Payment Date',
            'Period',
        ]);

        foreach ($payslips as $payslip) {
            $employee = $payslip->employee;
            $allowances = max((float) $payslip->gross_amount - (float) $payslip->basic_salary, 0);

            fputcsv($handle, [
                $employee?->barcode ?? $employee?->id,
                $employee?->name,
                $employee?->civil_id,
                '',
                $payslip->bank_account_number,
                '',
                $payslip->bank_account_number,
                number_format((float) $payslip->basic_salary, 3, '.', ''),
                number_format($allowances, 3, '.', ''),
                number_format((float) $payslip->deductions_amount, 3, '.', ''),
                number_format((float) $payslip->net_amount, 3, '.', ''),
                'OMR',
                $batch->pay_date?->format('Y-m-d'),
                sprintf('%02d/%d', $batch->period_month, $batch->period_year),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $csv;
    }
}
