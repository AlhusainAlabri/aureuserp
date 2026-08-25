<?php

namespace App\Mail\Accounting;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Webkul\Account\Models\Move;
use Webkul\Security\Models\User;

class OverdueInvoicesMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, Move>  $invoices
     */
    public function __construct(
        public readonly User $recipient,
        public readonly Collection $invoices,
        public readonly string $invoicesUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('accounting-extensions::notifications.overdue_invoices.subject', [
                'count' => $this->invoices->count(),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.accounting.overdue-invoices',
            with: [
                'recipient'   => $this->recipient,
                'invoices'    => $this->invoices,
                'invoicesUrl' => $this->invoicesUrl,
            ],
        );
    }
}
