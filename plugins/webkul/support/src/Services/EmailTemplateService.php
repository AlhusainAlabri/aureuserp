<?php

namespace Webkul\Support\Services;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Webkul\Support\Mail\DynamicEmail;
use Webkul\Support\Models\EmailLog;
use Webkul\Support\Models\EmailTemplate;

class EmailTemplateService
{
    public function getTemplate(string $templateCode, string $locale = 'en'): EmailTemplate
    {
        return EmailTemplate::query()
            ->where('code', $templateCode)
            ->where('is_active', true)
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    public function replaceVariables(string $content, array $variables): string
    {
        return preg_replace_callback('/\{\{(.*?)\}\}/', function (array $matches) use ($variables): string {
            $key = trim($matches[1]);

            return $variables[$key] ?? $matches[0];
        }, $content);
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    public function composeEmail(string $templateCode, array $variables = [], string $locale = 'en'): array
    {
        $template = $this->getTemplate($templateCode, $locale);

        return [
            'body'    => $this->replaceVariables($template->content, $variables),
            'subject' => $this->replaceVariables($template->subject, $variables),
            'layout'  => $template->layout,
            ...$variables,
        ];
    }

    /**
     * @param  array<string, mixed>  $variables
     * @param  array<int, array<string, mixed>>  $attachments
     */
    public function send(
        string $templateCode,
        string $recipientEmail,
        string $recipientName,
        array $variables = [],
        string $locale = 'en',
        array $attachments = [],
    ): bool {
        $emailData = $this->composeEmail($templateCode, $variables, $locale);
        $template = $this->getTemplate($templateCode, $locale);

        try {
            Mail::to($recipientEmail, $recipientName)
                ->queue((new DynamicEmail($emailData, Auth::user()))->withAttachments($attachments));

            $this->logEmail(
                templateId: $template->id,
                recipientEmail: $recipientEmail,
                recipientName: $recipientName,
                subject: $emailData['subject'],
                variables: $variables,
                status: 'queued',
                mailClass: DynamicEmail::class,
            );

            return true;
        } catch (Exception $exception) {
            $this->logEmail(
                templateId: $template->id,
                recipientEmail: $recipientEmail,
                recipientName: $recipientName,
                subject: $emailData['subject'] ?? '',
                variables: $variables,
                status: 'failed',
                errorMessage: $exception->getMessage(),
                mailClass: DynamicEmail::class,
            );

            throw $exception;
        }
    }

    protected function logEmail(
        int $templateId,
        string $recipientEmail,
        string $recipientName,
        string $subject,
        array $variables,
        string $status,
        ?string $errorMessage = null,
        ?string $mailClass = null,
    ): void {
        EmailLog::create([
            'email_template_id' => $templateId,
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
