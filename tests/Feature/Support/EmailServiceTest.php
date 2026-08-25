<?php

use App\Mail\InvoiceSentMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Webkul\Chatter\Mail\MessageMail;
use Webkul\Employee\Mail\EmployeeWarningMail;
use Webkul\Recruitment\Mail\ApplicationConfirmMail;
use Webkul\Security\Mail\UserInvitationMail;
use Webkul\Security\Models\Invitation;
use Webkul\Security\Models\User;
use Webkul\Support\Mail\DynamicEmail;
use Webkul\Support\Models\EmailLog;
use Webkul\Support\Models\EmailTemplate;
use Webkul\Support\Services\EmailService;
use Webkul\Support\Services\EmailTemplateService;
use Webkul\Support\Support\TransactionalMail;

beforeEach(function (): void {
    if (! Schema::hasTable('email_templates')) {
        Artisan::call('migrate', [
            '--path'           => 'plugins/webkul/support/database/migrations/2025_01_03_061444_create_email_templates_table.php',
            '--no-interaction' => true,
        ]);
    }

    if (Schema::hasTable('email_logs') && ! Schema::hasColumn('email_logs', 'mail_class')) {
        Artisan::call('migrate', [
            '--path'           => 'plugins/webkul/support/database/migrations/2026_08_25_200000_extend_email_logs_table.php',
            '--no-interaction' => true,
        ]);
    }
});

it('enriches transactional mail payload with configured from and authenticated reply-to', function (): void {
    $user = User::factory()->create([
        'email' => 'sender@example.com',
        'name'  => 'Sender User',
    ]);

    Auth::login($user);

    $payload = TransactionalMail::enrichPayload([
        'subject' => 'Test Subject',
        'to'      => [
            'address' => 'recipient@example.com',
            'name'    => 'Recipient',
        ],
    ]);

    expect($payload['from']['address'])->toBe(config('mail.from.address'))
        ->and($payload['from']['name'])->toBe(config('mail.from.name'))
        ->and($payload['reply_to']['address'])->toBe('sender@example.com')
        ->and($payload['reply_to']['name'])->toBe('Sender User');
});

it('queues emails through EmailService and logs queued status', function (): void {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'finance@example.com',
        'name'  => 'Finance User',
    ]);

    Auth::login($user);

    app(EmailService::class)->send(
        view: 'accounts::mail/invoice/actions/invoice',
        mailClass: InvoiceSentMail::class,
        payload: [
            'subject' => 'Invoice INV-001',
            'to'      => [
                'address' => 'customer@example.com',
                'name'    => 'Customer',
            ],
        ],
    );

    Mail::assertQueued(InvoiceSentMail::class, function (InvoiceSentMail $mail): bool {
        $envelope = $mail->envelope();

        return $mail->payload['subject'] === 'Invoice INV-001'
            && $envelope->from->address === config('mail.from.address')
            && $envelope->replyTo[0]->address === 'finance@example.com';
    });

    expect(EmailLog::query()->count())->toBe(1);

    $log = EmailLog::query()->first();

    expect($log->recipient_email)->toBe('customer@example.com')
        ->and($log->status)->toBe('queued')
        ->and($log->mail_class)->toBe(InvoiceSentMail::class)
        ->and($log->sender_user_id)->toBe($user->id);
});

it('queues template emails through EmailTemplateService and logs queued status', function (): void {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'hr@example.com',
        'name'  => 'HR User',
    ]);

    Auth::login($user);

    $template = EmailTemplate::factory()->create([
        'code'    => 'application-confirm',
        'subject' => 'Hello {{name}}',
        'content' => '<p>Welcome {{name}}</p>',
    ]);

    app(EmailTemplateService::class)->send(
        templateCode: $template->code,
        recipientEmail: 'applicant@example.com',
        recipientName: 'Applicant',
        variables: ['name' => 'Applicant'],
    );

    Mail::assertQueued(DynamicEmail::class);

    $log = EmailLog::query()->first();

    expect($log)->not->toBeNull()
        ->and($log->email_template_id)->toBe($template->id)
        ->and($log->status)->toBe('queued')
        ->and($log->variables)->toBe(['name' => 'Applicant']);
});

it('uses queued mailables for previously synchronous mail classes', function (): void {
    $invitation = new Invitation(['email' => 'invitee@example.com']);

    expect(new MessageMail('chatter::mail.message-mail', ['subject' => 'Test']))
        ->toBeInstanceOf(ShouldQueue::class)
        ->and(new ApplicationConfirmMail('recruitment::mail.confirm', ['subject' => 'Test']))
        ->toBeInstanceOf(ShouldQueue::class)
        ->and(new UserInvitationMail($invitation))
        ->toBeInstanceOf(ShouldQueue::class)
        ->and(is_subclass_of(EmployeeWarningMail::class, ShouldQueue::class))->toBeTrue();
});
