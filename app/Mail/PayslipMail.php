<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Webkul\Payroll\Models\Payslip;

class PayslipMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Payslip $payslip,
        public readonly string $pdfPath,
    ) {}

    public function envelope(): Envelope
    {
        $period = sprintf('%02d/%d', $this->payslip->period_month, $this->payslip->period_year);

        return new Envelope(
            subject: __('payroll::payroll.email.subject', ['period' => $period]),
        );
    }

    public function content(): Content
    {
        $period = sprintf('%02d/%d', $this->payslip->period_month, $this->payslip->period_year);

        return new Content(
            view: 'emails.payroll.payslip-notification',
            with: [
                'payslip' => $this->payslip,
                'period'  => $period,
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
                ->as('Payslip_'.$this->payslip->reference_number.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
