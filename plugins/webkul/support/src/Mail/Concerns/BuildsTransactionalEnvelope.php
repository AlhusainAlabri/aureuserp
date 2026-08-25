<?php

namespace Webkul\Support\Mail\Concerns;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;

trait BuildsTransactionalEnvelope
{
    /**
     * @param  array<string, mixed>  $payload
     */
    protected function transactionalEnvelope(array $payload): Envelope
    {
        $fromAddress = $payload['from']['address'] ?? config('mail.from.address');
        $fromName = $payload['from']['name'] ?? config('mail.from.name');

        $replyTo = [];

        if (! empty($payload['reply_to']['address'])) {
            $replyTo[] = new Address(
                $payload['reply_to']['address'],
                $payload['reply_to']['name'] ?? '',
            );
        }

        return new Envelope(
            subject: $payload['subject'],
            from: new Address($fromAddress, '"'.addslashes($fromName).'"'),
            replyTo: $replyTo,
        );
    }
}
