@component('mail::message')
# {{ __('hr-extensions::self_assessment.mail.reminder_heading') }}

{{ __('hr-extensions::self_assessment.mail.reminder_body', [
    'name' => $employee->name,
    'month' => __('hr-extensions::self_assessment.months.'.$month),
    'year' => $year,
]) }}

@component('mail::button', ['url' => url('/admin/my-self-assessment')])
{{ __('hr-extensions::self_assessment.mail.submit_button') }}
@endcomponent

@endcomponent
