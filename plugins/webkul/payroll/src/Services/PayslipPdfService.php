<?php

namespace Webkul\Payroll\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Webkul\Payroll\Models\Payslip;

class PayslipPdfService
{
    public function generate(Payslip $payslip): string
    {
        $payslip->loadMissing([
            'employee.department',
            'employee.job',
            'lines',
            'batch.company',
        ]);

        $pdf = Pdf::loadView('payroll::payroll.pdf.payslip', [
            'payslip'  => $payslip,
            'employee' => $payslip->employee,
            'company'  => $payslip->batch?->company,
            'lines'    => $payslip->lines,
        ])
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', true);

        $batchReference = str_replace('/', '-', $payslip->batch?->reference_number ?? 'batch');
        $payslipReference = str_replace('/', '-', $payslip->reference_number);
        $path = sprintf('payroll/payslips/%s/%s.pdf', $batchReference, $payslipReference);

        Storage::disk('private')->put($path, $pdf->output());

        return $path;
    }
}
