@component('mail::message')
# {{ __('assets-extensions::mail.rejected.heading') }}

{{ __('assets-extensions::notifications.rejected.body', [
    'asset' => $borrowing->asset?->name ?? '—',
    'number' => $borrowing->asset?->asset_number ?? '—',
    'employee' => $borrowing->employee?->name ?? '—',
    'due_at' => $borrowing->due_at?->translatedFormat('Y-m-d H:i') ?? '—',
]) }}

@include('mail.assets._borrowing-details', ['borrowing' => $borrowing])

@component('mail::button', ['url' => $viewUrl])
{{ __('assets-extensions::mail.rejected.action') }}
@endcomponent

{{ __('assets-extensions::mail.regards') }},<br>
{{ brand_name() }}
@endcomponent
