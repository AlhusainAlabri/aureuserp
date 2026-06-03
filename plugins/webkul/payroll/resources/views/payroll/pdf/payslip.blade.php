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
        .header { width: 100%; margin-bottom: 16px; }
        .muted { color: #6b7280; }
        .summary td { font-weight: bold; }
        .footer { position: fixed; bottom: -24px; left: 0; right: 0; text-align: center; font-size: 10px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <strong>{{ $company?->name }}</strong>
        <div class="muted">{{ __('payroll::payroll.pdf.payslip.title') }} — {{ $payslip->reference_number }}</div>
    </div>

    <h1>{{ __('payroll::payroll.pdf.payslip.title') }}</h1>

    <h2>{{ __('payroll::payroll.pdf.payslip.employee_details') }}</h2>
    <table>
        <tr>
            <th>{{ __('payroll::payroll.fields.employee') }}</th>
            <td>{{ $employee?->name }}</td>
            <th>{{ __('payroll::payroll.fields.period') }}</th>
            <td>{{ sprintf('%02d/%d', $payslip->period_month, $payslip->period_year) }}</td>
        </tr>
        <tr>
            <th>{{ __('payroll::payroll.fields.department') }}</th>
            <td>{{ $employee?->department?->name ?: '-' }}</td>
            <th>{{ __('payroll::payroll.fields.payment_method') }}</th>
            <td>{{ $payslip->payment_method?->getLabel() }}</td>
        </tr>
        <tr>
            <th>{{ __('payroll::payroll.fields.bank_name') }}</th>
            <td>{{ $payslip->bank_name ?: '-' }}</td>
            <th>{{ __('payroll::payroll.fields.bank_account_number') }}</th>
            <td>{{ $payslip->bank_account_number ?: '-' }}</td>
        </tr>
    </table>

    @php
        $symbol = app()->getLocale() === 'ar' ? __('payroll::payroll.currency.symbol_ar') : __('payroll::payroll.currency.symbol_en');
        $format = fn ($amount) => $symbol.' '.number_format((float) $amount, 3);
        $earnings = $lines->where('type.value', 'earning')->whenEmpty(fn ($c) => $lines->where('type', 'earning'));
        $deductions = $lines->where('type.value', 'deduction')->whenEmpty(fn ($c) => $lines->where('type', 'deduction'));
    @endphp

    <h2>{{ __('payroll::payroll.pdf.payslip.earnings') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('payroll::payroll.fields.code') }}</th>
                <th>{{ __('payroll::payroll.fields.name') }}</th>
                <th>{{ __('payroll::payroll.fields.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines->filter(fn ($line) => ($line->type->value ?? $line->type) === 'earning') as $line)
                <tr>
                    <td>{{ $line->code }}</td>
                    <td>{{ $line->name }}</td>
                    <td>{{ $format($line->amount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>{{ __('payroll::payroll.pdf.payslip.deductions') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('payroll::payroll.fields.code') }}</th>
                <th>{{ __('payroll::payroll.fields.name') }}</th>
                <th>{{ __('payroll::payroll.fields.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines->filter(fn ($line) => ($line->type->value ?? $line->type) === 'deduction') as $line)
                <tr>
                    <td>{{ $line->code }}</td>
                    <td>{{ $line->name }}</td>
                    <td>{{ $format($line->amount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>{{ __('payroll::payroll.pdf.payslip.summary') }}</h2>
    <table class="summary">
        <tr>
            <th>{{ __('payroll::payroll.fields.basic_salary') }}</th>
            <td>{{ $format($payslip->basic_salary) }}</td>
            <th>{{ __('payroll::payroll.fields.gross_amount') }}</th>
            <td>{{ $format($payslip->gross_amount) }}</td>
        </tr>
        <tr>
            <th>{{ __('payroll::payroll.fields.deductions_amount') }}</th>
            <td>{{ $format($payslip->deductions_amount) }}</td>
            <th>{{ __('payroll::payroll.fields.net_amount') }}</th>
            <td>{{ $format($payslip->net_amount) }}</td>
        </tr>
    </table>

    <div class="footer">{{ __('payroll::payroll.pdf.payslip.footer', ['reference' => $payslip->reference_number, 'page' => '{PAGE_NUM}']) }}</div>
</body>
</html>
