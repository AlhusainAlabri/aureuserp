<?php

namespace App\Mail\Tasks;

use App\Services\Projects\TaskStatePresenter;
use App\Support\FilamentUrl;
use Filament\Facades\Filament;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Webkul\Project\Filament\Resources\TaskResource;
use Webkul\Project\Models\Task;
use Webkul\Security\Models\User;

class TaskDeadlineReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Task $task,
        public readonly User $recipient,
        public readonly int $daysUntilDue,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->daysUntilDue < 0
            ? __('tasks.mail.overdue.subject', ['title' => $this->task->title])
            : __('tasks.mail.deadline.subject', ['title' => $this->task->title]);

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return new Content(
            markdown: 'mail.tasks.deadline-reminder',
            with: [
                'task'         => $this->task,
                'recipient'    => $this->recipient,
                'daysUntilDue' => $this->daysUntilDue,
                'statusLabel'  => TaskStatePresenter::label($this->task->state),
                'deadline'     => $this->task->deadline?->locale(app()->getLocale())->translatedFormat('d M Y'),
                'taskUrl'      => FilamentUrl::appendLocaleToUrl(
                    TaskResource::getUrl('view', ['record' => $this->task]),
                ),
            ],
        );
    }
}
