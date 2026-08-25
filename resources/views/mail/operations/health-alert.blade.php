# {{ __('operations.alerts.health_heading', ['status' => $status->getLabel()]) }}

**{{ $context['label'] ?? $component }}**

{{ $context['summary'] ?? '' }}

@if (! empty($context['message']))
> {{ $context['message'] }}
@endif

{{ __('operations.alerts.health_footer') }}
