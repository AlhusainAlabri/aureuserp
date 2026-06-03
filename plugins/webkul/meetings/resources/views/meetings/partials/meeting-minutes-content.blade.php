<div class="header">
    <div>
        <strong>{{ $meeting->company?->name }}</strong>
    </div>
    <div>
        <div>{{ __('meetings::meetings.fields.meeting_number') }}: {{ $meeting->meeting_number }}</div>
        <div>{{ __('meetings::meetings.fields.meeting_date') }}: {{ $meeting->meeting_date?->translatedFormat('d M Y H:i') }}</div>
    </div>
</div>

<h1>{{ __('meetings::meetings.pdf.title') }}</h1>

<h2>{{ __('meetings::meetings.pdf.sections.meeting_data') }}</h2>
<table>
    <tr>
        <th>{{ __('meetings::meetings.fields.type') }}</th>
        <td>{{ __('meetings::meetings.types.'.$meeting->type) }}</td>
        <th>{{ __('meetings::meetings.fields.location') }}</th>
        <td>{{ $meeting->location ?: '-' }}</td>
    </tr>
    <tr>
        <th>{{ __('meetings::meetings.fields.duration_minutes') }}</th>
        <td>{{ $meeting->duration_minutes ?: '-' }}</td>
        <th>{{ __('meetings::meetings.fields.project') }}</th>
        <td>{{ $meeting->project?->name ?: '-' }}</td>
    </tr>
    <tr>
        <th>{{ __('meetings::meetings.fields.chair_person') }}</th>
        <td>{{ $meeting->chairPerson?->name }}</td>
        <th>{{ __('meetings::meetings.fields.secretary') }}</th>
        <td>{{ $meeting->secretary?->name ?: '-' }}</td>
    </tr>
</table>

<h2>{{ __('meetings::meetings.pdf.sections.attendees') }}</h2>
<table>
    <thead>
        <tr>
            <th>{{ __('meetings::meetings.fields.user') }}</th>
            <th>{{ __('meetings::meetings.fields.role') }}</th>
            <th>{{ __('meetings::meetings.fields.attended') }}</th>
            <th>{{ __('meetings::meetings.fields.signed_at') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($meeting->attendees as $attendee)
            <tr>
                <td>{{ $attendee->user?->name }}</td>
                <td>{{ __('meetings::meetings.roles.'.$attendee->role) }}</td>
                <td>{{ $attendee->attended ? '✓' : '✗' }}</td>
                <td>{{ $attendee->signed_at ? '✓' : '✗' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<h2>{{ __('meetings::meetings.pdf.sections.agenda') }}</h2>
{!! $meeting->agenda ?: '<span class="muted">-</span>' !!}

<h2>{{ __('meetings::meetings.pdf.sections.approvals') }}</h2>
<table>
    <thead>
        <tr>
            <th>{{ __('meetings::meetings.fields.status') }}</th>
            <th>{{ __('meetings::meetings.fields.user') }}</th>
            <th>{{ __('meetings::meetings.fields.date') }}</th>
            <th>{{ __('meetings::meetings.fields.notes') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($meeting->approvals->flatMap->actions as $approvalAction)
            <tr>
                <td>{{ $approvalAction->type?->getLabel() ?? $approvalAction->type }}</td>
                <td>{{ $approvalAction->user?->name ?: '-' }}</td>
                <td>{{ $approvalAction->created_at?->translatedFormat('d M Y H:i') }}</td>
                <td>{{ $approvalAction->comment ?: '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<h2>{{ __('meetings::meetings.pdf.sections.tasks') }}</h2>
<table>
    <thead>
        <tr>
            <th>{{ __('meetings::meetings.fields.task_title') }}</th>
            <th>{{ __('meetings::meetings.fields.assigned_to') }}</th>
            <th>{{ __('meetings::meetings.fields.due_date') }}</th>
            <th>{{ __('meetings::meetings.fields.status') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($meeting->tasks as $task)
            <tr>
                <td>{{ $task->title }}</td>
                <td>{{ $task->assignee?->name }}</td>
                <td>{{ $task->due_date?->translatedFormat('d M Y') ?: '-' }}</td>
                <td>{{ __('meetings::meetings.task_statuses.'.$task->status) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<h2>{{ __('meetings::meetings.pdf.sections.notes') }}</h2>
{!! $meeting->notes ?: '<span class="muted">-</span>' !!}

<h2>{{ __('meetings::meetings.pdf.sections.attachments') }}</h2>
<ul>
    @foreach ($meeting->attachments as $attachment)
        <li>{{ $attachment->file_name }}</li>
    @endforeach
</ul>

<div class="footer">
    {{ __('meetings::meetings.pdf.footer', ['number' => $meeting->meeting_number, 'date' => now()->translatedFormat('d M Y')]) }}
</div>
