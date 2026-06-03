<?php

namespace Webkul\MyNotes\Console\Commands;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;
use Webkul\MyNotes\Filament\Pages\MyNotesPage;
use Webkul\MyNotes\Mail\NoteReminderMail;
use Webkul\MyNotes\Models\Note;
use Webkul\PluginManager\Package;

class SendNoteReminders extends Command
{
    protected $signature = 'notes:send-reminders';

    protected $description = 'Send notifications and emails for due note reminders.';

    public function handle(): int
    {
        if (! Package::isPluginInstalled('my-notes') || ! Schema::hasTable('notes')) {
            $this->components->info('My Notes is not installed. Reminder dispatch skipped.');

            return self::SUCCESS;
        }

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
            $emailQueued = $this->sendEmail($note);

            $note->update([
                'reminder_sent'       => true,
                'reminder_email_sent' => $emailQueued,
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
                    ->label(__('my-notes::notes.actions.view'))
                    ->url(MyNotesPage::reminderUrl()),
            ])
            ->sendToDatabase($note->user);
    }

    protected function sendEmail(Note $note): bool
    {
        if (empty($note->user?->email)) {
            return false;
        }

        try {
            Mail::to($note->user->email)->queue(new NoteReminderMail($note));

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }
}
