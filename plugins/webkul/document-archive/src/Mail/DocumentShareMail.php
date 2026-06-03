<?php

namespace Webkul\DocumentArchive\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Models\DocShareLink;

class DocumentShareMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly DocFile $file,
        public readonly DocShareLink $link,
    ) {}

    public function build(): static
    {
        return $this
            ->subject(__('document-archive::document-archive.share.email_subject', [
                'reference' => $this->file->reference_number,
            ]))
            ->markdown('document-archive::emails.document-share', [
                'file' => $this->file,
                'link' => $this->link,
                'url'  => $this->link->getPublicUrl(),
            ]);
    }
}
