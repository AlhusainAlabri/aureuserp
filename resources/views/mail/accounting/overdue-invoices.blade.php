<x-mail::message>
# {{ __('accounting-extensions::notifications.overdue_invoices.heading', ['count' => $invoices->count()]) }}

@foreach ($invoices->take(10) as $invoice)
- **{{ $invoice->name }}** — {{ $invoice->partner?->name ?? '—' }} — {{ __('accounting-extensions::notifications.overdue_invoices.due', ['date' => $invoice->invoice_date_due?->format('Y-m-d') ?? '—']) }} — {{ \App\Support\OmrFormatter::format($invoice->amount_residual ?? 0) }}
@endforeach

@if ($invoices->count() > 10)
{{ __('accounting-extensions::notifications.overdue_invoices.more', ['count' => $invoices->count() - 10]) }}
@endif

<x-mail::button :url="$invoicesUrl">
{{ __('accounting-extensions::notifications.overdue_invoices.action') }}
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
