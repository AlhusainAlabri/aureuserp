<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; direction: {{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}; text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }}; color: #111827; font-size: 12px; }
        h1 { text-align: center; font-size: 22px; margin: 8px 0 16px; }
        h2 { font-size: 14px; margin: 16px 0 8px; border-bottom: 1px solid #d1d5db; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; }
        .footer { position: fixed; bottom: -24px; left: 0; right: 0; text-align: center; font-size: 10px; color: #6b7280; }
    </style>
</head>
<body>
    @php
        $symbol = app()->getLocale() === 'ar' ? __('payroll::payroll.currency.symbol_ar') : __('payroll::payroll.currency.symbol_en');
        $format = fn ($amount) => $symbol.' '.number_format((float) $amount, 3);
    @endphp

    <div>
        <strong>{{ $company?->name }}</strong>
        <div>{{ __('payroll::payroll.pdf.register.title') }} — {{ $batch->reference_number }}</div>
    </div>

    <h1>{{ __('payroll::payroll.pdf.register.title') }}</h1>

    <h2>{{ __('payroll::payroll.pdf.register.summary') }}</h2>
    <table>
        <tr>
            <th>{{ __('payroll::payroll.pdf.register.period') }}</th>
            <td>{{ sprintf('%02d/%d', $batch->period_month, $batch->period_year) }}</td>
            <th>{{ __('payroll::payroll.fields.pay_date') }}</th>
            <td>{{ $batch->pay_date?->format('Y-m-d') }}</td>
        </tr>
        <tr>
            <th>{{ __('payroll::payroll.fields.employee_count') }}</th>
            <td>{{ $batch->employee_count }}</td>
            <th>{{ __('payroll::payroll.fields.status') }}</th>
            <td>{{ $batch->status?->getLabel() }}</td>
        </tr>
        <tr>
            <th>{{ __('payroll::payroll.fields.total_gross') }}</th>
            <td>{{ $format($batch->total_gross) }}</td>
            <th>{{ __('payroll::payroll.fields.total_deductions') }}</th>
            <td>{{ $format($batch->total_deductions) }}</td>
        </tr>
        <tr>
            <th>{{ __('payroll::payroll.fields.total_net') }}</th>
            <td colspan="3">{{ $format($batch->total_net) }}</td>
        </tr>
    </table>

    <h2>{{ __('payroll::payroll.pdf.register.employees') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('payroll::payroll.fields.reference_number') }}</th>
                <th>{{ __('payroll::payroll.fields.employee') }}</th>
                <th>{{ __('payroll::payroll.fields.basic_salary') }}</th>
                <th>{{ __('payroll::payroll.fields.gross_amount') }}</th>
                <th>{{ __('payroll::payroll.fields.deductions_amount') }}</th>
                <th>{{ __('payroll::payroll.fields.net_amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payslips as $payslip)
                <tr>
                    <td>{{ $payslip->reference_number }}</td>
                    <td>{{ $payslip->employee?->name }}</td>
                    <td>{{ $format($payslip->basic_salary) }}</td>
                    <td>{{ $format($payslip->gross_amount) }}</td>
                    <td>{{ $format($payslip->deductions_amount) }}</td>
                    <td>{{ $format($payslip->net_amount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">{{ __('payroll::payroll.pdf.register.footer', ['reference' => $batch->reference_number, 'page' => '{PAGE_NUM}']) }}</div>
</body>
</html>
