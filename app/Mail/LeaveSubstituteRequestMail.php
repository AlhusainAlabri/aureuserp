<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Webkul\TimeOff\Models\Leave;

class LeaveSubstituteRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Leave $leave,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('hr-extensions::leave.notifications.substitute_request.title'),
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: nl2br(e(__('hr-extensions::leave.notifications.substitute_request.body', [
                'employee' => $this->leave->employee?->name ?? '—',
                'start'    => $this->formatDate($this->leave->date_from),
                'end'      => $this->formatDate($this->leave->date_to),
            ]).($this->leave->substitute_notes ? "\n\n".$this->leave->substitute_notes : ''))),
        );
    }

    private function formatDate(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        return Carbon::parse($value)->format('Y-m-d');
    }
}
