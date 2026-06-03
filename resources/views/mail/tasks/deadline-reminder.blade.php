<x-mail::message>
# {{ $daysUntilDue < 0 ? __('tasks.mail.overdue.heading') : __('tasks.mail.deadline.heading') }}

{{ $daysUntilDue < 0 ? __('tasks.mail.overdue.intro', ['name' => $recipient->name]) : __('tasks.mail.deadline.intro', ['name' => $recipient->name]) }}

**{{ $task->title }}**

- **{{ __('tasks.mail.status') }}:** {{ $statusLabel }}
- **{{ __('tasks.mail.deadline_label') }}:** {{ $deadline ?? '—' }}

<x-mail::button :url="url('/admin/project/tasks/' . $task->id)">
{{ __('tasks.hub.view_all_tasks') }}
</x-mail::button>

{{ brand_name() }}
</x-mail::message>
