<?php

namespace Webkul\Support\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Webkul\Support\Mail\Concerns\BuildsTransactionalEnvelope;
use Webkul\Support\Support\TransactionalMail;

class DynamicEmail extends Mailable implements ShouldQueue
{
    use BuildsTransactionalEnvelope;
    use Queueable, SerializesModels;

    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $attachmentData = [];

    /**
     * @param  array<string, mixed>  $emailData
     */
    public function __construct(
        public array $emailData,
        public ?Authenticatable $sender = null,
    ) {
        $this->emailData = TransactionalMail::enrichPayload($emailData, $sender);
    }

    public function envelope(): Envelope
    {
        return $this->transactionalEnvelope($this->emailData);
    }

    public function content(): Content
    {
        return new Content(
            view: 'support::mail.dynamic-email',
            with: [
                'body' => $this->emailData['body'] ?? '',
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        foreach ($this->attachmentData as $attachment) {
            if (isset($attachment['path'])) {
                $disk = $attachment['disk'] ?? $this->resolveAttachmentDisk($attachment['path']);

                $attachments[] = Attachment::fromStorageDisk($disk, $attachment['path'])
                    ->as($attachment['name'] ?? null)
                    ->withMime($attachment['mime'] ?? null);
            } elseif (isset($attachment['data'])) {
                $attachments[] = Attachment::fromData(
                    fn () => $attachment['data'],
                    $attachment['name'],
                )->withMime($attachment['mime'] ?? null);
            }
        }

        return $attachments;
    }

    /**
     * @param  array<int, array<string, mixed>>  $attachments
     */
    public function withAttachments(array $attachments): static
    {
        $this->attachmentData = $attachments;

        return $this;
    }

    protected function resolveAttachmentDisk(string $path): string
    {
        if (Storage::disk('private')->exists($path)) {
            return 'private';
        }

        return 'public';
    }
}
