@component('mail::message')
# {{ __('my-notes::notes.notify.reminder_title', ['title' => $note->auto_title]) }}

{{ strip_tags($note->body ?? '') }}

**{{ __('my-notes::notes.reminder_at') }}:** {{ $note->reminder_at?->format('d M Y h:i A') }}

@component('mail::button', ['url' => \Webkul\MyNotes\Filament\Pages\MyNotesPage::reminderUrl()])
{{ __('my-notes::notes.view_all_notes') }}
@endcomponent

{{ __('Thanks') }},<br>
{{ config('app.name') }}
@endcomponent
