@php
    $titleKey = "approval-notifications.{$notificationKey}.title";
    $bodyKey = "approval-notifications.{$notificationKey}.body";
@endphp

<x-mail::message>
# {{ __($titleKey, ['step' => $stepName ?? '']) }}

{{ __($bodyKey, array_filter([
    'record' => $recordLabel,
    'step' => $stepName,
    'deadline' => $deadline?->diffForHumans(),
])) }}

@if ($viewUrl)
<x-mail::button :url="$viewUrl">
{{ __('approval-notifications.actions.view') }}
</x-mail::button>
@endif

{{ __('approval-notifications.mail.footer', ['app' => config('app.name')]) }}
</x-mail::message>
