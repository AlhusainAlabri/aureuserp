<?php

namespace Webkul\MyNotes\Console\Commands;

use App\Services\Notifications\NotificationChannel;
use App\Services\Notifications\NotificationDefinition;
use App\Services\Notifications\NotificationDispatcher;
use Filament\Actions\Action;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Webkul\MyNotes\Filament\Pages\MyNotesPage;
use Webkul\MyNotes\Mail\NoteReminderMail;
use Webkul\MyNotes\Models\Note;
use Webkul\PluginManager\Package;

class SendNoteReminders extends Command
{
    protected $signature = 'notes:send-reminders';

    protected $description = 'Send notifications and emails for due note reminders.';

    public function handle(NotificationDispatcher $notifications): int
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

            $emailQueued = filled($note->user->email);

            try {
                $notifications->dispatch(
                    NotificationDefinition::make('notes.reminder')
                        ->recipients([$note->user])
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
                        ->channels([
                            NotificationChannel::Database,
                            NotificationChannel::Mail,
                        ])
                        ->emailWhen(fn () => $emailQueued)
                        ->mail(fn () => new NoteReminderMail($note))
                        ->build(),
                );
            } catch (\Throwable $exception) {
                report($exception);
                $emailQueued = false;
            }

            $note->update([
                'reminder_sent'         => true,
                'reminder_email_sent'   => $emailQueued,
            ]);
        }

        $this->info("Sent {$notes->count()} reminder notifications.");

        return self::SUCCESS;
    }
}
