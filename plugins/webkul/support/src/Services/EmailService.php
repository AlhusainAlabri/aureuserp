<?php

namespace Webkul\Support\Services;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Webkul\Support\Models\EmailLog;
use Webkul\Support\Support\TransactionalMail;

class EmailService
{
    /**
     * @param  array<int, array<string, mixed>>  $attachments
     */
    public function send(string $view, string $mailClass, array $payload, array $attachments = []): bool
    {
        $payload = TransactionalMail::enrichPayload($payload);

        try {
            Mail::to($payload['to']['address'], '"'.addslashes($payload['to']['name']).'"')
                ->queue((new $mailClass($view, $payload))->withAttachments($attachments));

            $this->logEmail(
                recipientEmail: $payload['to']['address'],
                recipientName: $payload['to']['name'],
                subject: $payload['subject'],
                status: 'queued',
                mailClass: $mailClass,
            );

            return true;
        } catch (Exception $exception) {
            $this->logEmail(
                recipientEmail: $payload['to']['address'],
                recipientName: $payload['to']['name'],
                subject: $payload['subject'],
                status: 'failed',
                errorMessage: $exception->getMessage(),
                mailClass: $mailClass,
            );

            throw $exception;
        }
    }

    protected function logEmail(
        string $recipientEmail,
        string $recipientName,
        string $subject,
        string $status,
        ?string $errorMessage = null,
        ?string $mailClass = null,
        ?int $emailTemplateId = null,
        ?array $variables = null,
    ): void {
        EmailLog::create([
            'email_template_id' => $emailTemplateId,
            'recipient_email'   => $recipientEmail,
            'recipient_name'    => $recipientName,
            'subject'           => $subject,
            'variables'         => $variables,
            'sender_user_id'    => Auth::id(),
            'mail_class'        => $mailClass,
            'status'            => $status,
            'error_message'     => $errorMessage,
            'sent_at'           => now(),
        ]);
    }
}
