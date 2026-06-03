<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('payroll::payroll.email.subject', ['period' => $period]) }}</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6;">
    <p>{{ __('payroll::payroll.email.greeting', ['name' => $payslip->employee?->name ?? '']) }}</p>
    <p>{{ __('payroll::payroll.email.body', ['period' => $period]) }}</p>
    <p>{{ __('payroll::payroll.email.net', ['amount' => number_format((float) $payslip->net_amount, 3)]) }}</p>
    <p>{{ __('payroll::payroll.email.footer') }}</p>
</body>
</html>
