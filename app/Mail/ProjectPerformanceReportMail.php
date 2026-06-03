<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Webkul\Project\Models\Project;

class ProjectPerformanceReportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Project $project,
        public readonly string $pdfPath,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('projects-extensions::reports.email_subject', [
                'project' => $this->project->name,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.projects.performance-report',
            with: [
                'project' => $this->project,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('private', $this->pdfPath)
                ->as('project-performance-'.$this->project->id.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
