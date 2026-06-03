<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('purchases-extensions::request.email.receipt_reminder.subject', ['reference' => $order->name]) }}</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6;">
    <p>{{ __('purchases-extensions::request.email.receipt_reminder.greeting', ['name' => $recipient->name]) }}</p>
    <p>{{ __('purchases-extensions::request.email.receipt_reminder.body', ['reference' => $order->name]) }}</p>
    <p>{{ __('purchases-extensions::request.email.receipt_reminder.footer') }}</p>
</body>
</html>
