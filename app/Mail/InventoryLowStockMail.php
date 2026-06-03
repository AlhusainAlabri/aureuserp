<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Webkul\Inventory\Models\OrderPoint;

class InventoryLowStockMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, OrderPoint>  $orderPoints
     */
    public function __construct(
        public readonly Collection $orderPoints,
        public readonly string $replenishmentUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('inventory-extensions::mail.low_stock_subject', [
                'count' => $this->orderPoints->count(),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.inventory.low-stock',
            with: [
                'orderPoints'     => $this->orderPoints,
                'replenishmentUrl'=> $this->replenishmentUrl,
            ],
        );
    }
}
