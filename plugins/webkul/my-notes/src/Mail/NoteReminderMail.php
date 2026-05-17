<?php

namespace Webkul\MyNotes\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Webkul\MyNotes\Models\Note;

class NoteReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Note $note) {}

    public function build(): static
    {
        return $this
            ->subject(__('my-notes::notes.notify.reminder_title', ['title' => $this->note->auto_title]))
            ->markdown('my-notes::emails.reminder', [
                'note' => $this->note,
            ]);
    }
}
