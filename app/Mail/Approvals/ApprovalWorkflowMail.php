<?php

namespace App\Mail\Approvals;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Webkul\Security\Models\User;

class ApprovalWorkflowMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $recipient,
        public readonly string $notificationKey,
        public readonly string $recordLabel,
        public readonly ?string $stepName = null,
        public readonly ?string $viewUrl = null,
        public readonly ?CarbonInterface $deadline = null,
    ) {}

    public function envelope(): Envelope
    {
        $titleKey = "approval-notifications.{$this->notificationKey}.title";

        return new Envelope(
            subject: __($titleKey, ['step' => $this->stepName ?? '']),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.approvals.workflow',
            with: [
                'recipient'       => $this->recipient,
                'notificationKey' => $this->notificationKey,
                'recordLabel'     => $this->recordLabel,
                'stepName'        => $this->stepName,
                'viewUrl'         => $this->viewUrl,
                'deadline'        => $this->deadline,
            ],
        );
    }
}
