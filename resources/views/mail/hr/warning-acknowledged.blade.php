@component('mail::message')
# {{ __('hr-extensions::warnings.mail.acknowledged_heading') }}

{{ __('hr-extensions::warnings.mail.acknowledged_body', [
    'employee' => $employee?->name ?? '',
    'subject' => $warning->subject,
    'date' => $warning->issued_at?->format('Y-m-d') ?? '',
]) }}

{{ __('hr-extensions::warnings.mail.thanks') }}

@endcomponent
