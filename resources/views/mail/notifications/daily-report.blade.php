<x-mail::message>
# {{ $report['healthy'] ? __('notifications.operations.report.mail.heading_ok') : __('notifications.operations.report.mail.heading_alert') }}

{{ __('notifications.operations.report.mail.period', [
    'from' => $report['period']['from'],
    'to' => $report['period']['to'],
]) }}

## {{ __('notifications.operations.report.mail.email_section') }}

- {{ __('notifications.operations.report.email_queued') }}: **{{ $report['email_logs']['queued'] }}**
- {{ __('notifications.operations.report.email_sent') }}: **{{ $report['email_logs']['sent'] }}**
- {{ __('notifications.operations.report.email_failed') }}: **{{ $report['email_logs']['failed'] }}**
- {{ __('notifications.operations.report.email_failure_rate') }}: **{{ number_format($report['email_logs']['failure_rate'] * 100, 2) }}%**

## {{ __('notifications.operations.report.mail.queue_section') }}

- {{ __('notifications.operations.report.failed_jobs_period') }}: **{{ $report['failed_jobs']['last_period'] }}**
- {{ __('notifications.operations.report.failed_jobs_unresolved') }}: **{{ $report['failed_jobs']['unresolved'] }}**
- {{ __('notifications.operations.report.queue_pending') }}: **{{ $report['queue']['pending'] }}**
- {{ __('notifications.operations.report.database_notifications') }}: **{{ $report['database_notifications']['created'] }}**

@if (! empty($report['alerts']))
## {{ __('notifications.operations.report.alerts_heading') }}

@foreach ($report['alerts'] as $alert)
- {{ $alert }}
@endforeach
@endif

@if (! empty($report['failed_jobs']['recent']))
## {{ __('notifications.operations.report.mail.recent_failures') }}

@foreach ($report['failed_jobs']['recent'] as $failure)
- #{{ $failure['id'] }} ({{ $failure['queue'] }}) — {{ $failure['failed_at'] }} — {{ $failure['exception'] }}
@endforeach
@endif

{{ config('app.name') }}
</x-mail::message>
