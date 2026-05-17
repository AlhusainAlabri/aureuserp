<?php

namespace Webkul\MyNotes\Console\Commands;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Webkul\MyNotes\Mail\NoteReminderMail;
use Webkul\MyNotes\Models\Note;

class SendNoteReminders extends Command
{
    protected $signature = 'notes:send-reminders';

    protected $description = 'Send notifications and emails for due note reminders.';

    public function handle(): int
    {
        $notes = Note::withoutGlobalScopes()
            ->where('type', 'reminder')
            ->where('reminder_at', '<=', now())
            ->where('reminder_sent', false)
            ->where('is_archived', false)
            ->with('user')
            ->get();

        foreach ($notes as $note) {
            if ($note->user === null) {
                continue;
            }

            $this->sendDatabaseNotification($note);
            $this->sendEmail($note);

            $note->update([
                'reminder_sent'       => true,
                'reminder_email_sent' => true,
            ]);
        }

        $this->info("Sent {$notes->count()} reminder notifications.");

        return self::SUCCESS;
    }

    protected function sendDatabaseNotification(Note $note): void
    {
        Notification::make()
            ->title(__('my-notes::notes.notify.reminder_title', ['title' => $note->auto_title]))
            ->body(__('my-notes::notes.notify.reminder_body', [
                'time' => $note->reminder_at?->format('h:i A') ?? '-',
                'body' => strip_tags($note->body ?? ''),
            ]))
            ->actions([
                Action::make('view')
                    ->label(__('my-notes::notes.view_all_notes'))
                    ->url('/admin/my-notes'),
            ])
            ->sendToDatabase($note->user);
    }

    protected function sendEmail(Note $note): void
    {
        if (empty($note->user?->email)) {
            return;
        }

        Mail::to($note->user->email)->queue(new NoteReminderMail($note));
    }
}
