<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Webkul\Support\Mail\Concerns\BuildsTransactionalEnvelope;

class SaleOrderCancelQuotation extends Mailable implements ShouldQueue
{
    use BuildsTransactionalEnvelope;
    use Queueable, SerializesModels;

    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $attachmentData = [];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $viewTemplate,
        public array $payload,
    ) {}

    public function envelope(): Envelope
    {
        return $this->transactionalEnvelope($this->payload);
    }

    public function content(): Content
    {
        return new Content(view: $this->viewTemplate);
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
