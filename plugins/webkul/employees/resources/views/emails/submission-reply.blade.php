<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('employees::mail/submission-reply.subject', ['type' => __('employees::filament/resources/submission.types.'.$submission->type), 'ticket' => $submission->ticket_number]) }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .reply-box { background: #e8f4fd; padding: 16px; border-radius: 8px; margin: 16px 0; border-left: 4px solid #0ea5e9; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{ __('employees::mail/submission-reply.header') }}</h2>
            <p><strong>{{ $submission->ticket_number }}</strong> — {{ $submission->subject }}</p>
        </div>

        <div class="reply-box">
            <p><strong>{{ __('employees::mail/submission-reply.response') }}</strong></p>
            <p>{{ $reply->body }}</p>
        </div>

        <div class="footer">
            <p>{{ __('employees::mail/submission-reply.footer') }}</p>
        </div>
    </div>
</body>
</html>
