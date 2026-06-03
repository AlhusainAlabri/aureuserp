<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Webkul\Purchase\Models\PurchaseOrder;
use Webkul\Security\Models\User;

class PurchaseReceiptReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly PurchaseOrder $order,
        public readonly User $recipient,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('purchases-extensions::request.email.receipt_reminder.subject', [
                'reference' => $this->order->name,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.purchases.receipt-reminder',
            with: [
                'order'     => $this->order,
                'recipient' => $this->recipient,
            ],
        );
    }
}
