<?php

namespace App\Console\Commands;

use App\Mail\Operations\SystemTestMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendSystemTestEmail extends Command
{
    protected $signature = 'system:send-test-email {email? : Recipient email address}';

    protected $description = 'Send a test operations email using the configured mailer';

    public function handle(): int
    {
        $email = $this->argument('email') ?: auth()->user()?->email;

        if (! filled($email)) {
            $this->error(__('operations.notifications.test_email_missing'));

            return self::FAILURE;
        }

        Mail::to($email)->queue(new SystemTestMail);

        $this->info(__('operations.notifications.test_email_sent', ['email' => $email]));

        return self::SUCCESS;
    }
}
