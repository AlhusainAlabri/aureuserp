<?php

use App\Mail\LeaveSubstituteRequestMail;
use App\Models\Projects\UserTaskReminderPreference;
use App\Models\UserNotificationPreference;
use App\Services\Notifications\NotificationChannel;
use App\Services\Notifications\NotificationDefinition;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Notifications\DatabaseNotification as LaravelDatabaseNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Webkul\Security\Models\User;
use Webkul\TimeOff\Models\Leave;

beforeEach(function (): void {
    if (! Schema::hasTable('user_notification_preferences')) {
        Artisan::call('migrate', [
            '--path'  => 'database/migrations/2026_08_25_161202_create_user_notification_preferences_table.php',
            '--force' => true,
        ]);
    }
});

it('dispatches database and mail channels through the notification dispatcher', function (): void {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'recipient@example.com',
    ]);

    app(NotificationDispatcher::class)->dispatch(
        NotificationDefinition::make('tests.dual_channel')
            ->recipients([$user])
            ->title('Test notification')
            ->body('Test body')
            ->icon('heroicon-o-bell')
            ->color('warning')
            ->channels([NotificationChannel::Database, NotificationChannel::Mail])
            ->mail(fn (User $recipient) => new LeaveSubstituteRequestMail(
                new Leave(['state' => 'confirm']),
            ))
            ->build(),
    );

    Mail::assertQueued(LeaveSubstituteRequestMail::class);

    expect($user->fresh()->notifications)->toHaveCount(1);

    $databaseNotification = $user->fresh()->notifications->first();

    expect($databaseNotification)->toBeInstanceOf(LaravelDatabaseNotification::class)
        ->and($databaseNotification->data['title'] ?? null)->toBe('Test notification');
});

it('respects per-user channel preference closures', function (): void {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'tasks@example.com',
    ]);

    UserTaskReminderPreference::query()->create([
        'user_id'                  => $user->id,
        'email_enabled'            => false,
        'database_enabled'         => true,
        'notify_same_day'          => true,
        'notify_one_day_before'    => true,
        'notify_three_days_before' => true,
        'notify_seven_days_before' => false,
    ]);

    app(NotificationDispatcher::class)->dispatch(
        NotificationDefinition::make('tests.preferences')
            ->recipients([$user])
            ->title('Preference test')
            ->body('Only database')
            ->channels([NotificationChannel::Database, NotificationChannel::Mail])
            ->databaseWhen(fn (User $recipient): bool => UserTaskReminderPreference::query()
                ->where('user_id', $recipient->id)
                ->value('database_enabled') ?? true)
            ->emailWhen(fn (User $recipient): bool => UserTaskReminderPreference::query()
                ->where('user_id', $recipient->id)
                ->value('email_enabled') ?? true)
            ->mail(fn () => new LeaveSubstituteRequestMail(
                new Leave(['state' => 'confirm']),
            ))
            ->build(),
    );

    Mail::assertNothingQueued();
    expect($user->fresh()->notifications)->toHaveCount(1);
});

it('deduplicates recipients before dispatching', function (): void {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'once@example.com',
    ]);

    app(NotificationDispatcher::class)->dispatch(
        NotificationDefinition::make('tests.dedupe')
            ->recipients([$user, $user])
            ->title('Once')
            ->body('Once')
            ->channels([NotificationChannel::Database])
            ->build(),
    );

    expect($user->fresh()->notifications)->toHaveCount(1);
});

it('respects global category preferences when no explicit closure is provided', function (): void {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'global@example.com',
    ]);

    UserNotificationPreference::query()->create([
        'user_id'          => $user->id,
        'category'         => 'hr',
        'database_enabled' => true,
        'email_enabled'    => false,
    ]);

    app(NotificationDispatcher::class)->dispatch(
        NotificationDefinition::make('hr.leave.substitute_request')
            ->recipients([$user])
            ->title('HR alert')
            ->body('Only database')
            ->channels([NotificationChannel::Database, NotificationChannel::Mail])
            ->mail(fn () => new LeaveSubstituteRequestMail(
                new Leave(['state' => 'confirm']),
            ))
            ->build(),
    );

    Mail::assertNothingQueued();
    expect($user->fresh()->notifications)->toHaveCount(1);
});

it('builder dispatch helper routes through the dispatcher', function (): void {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'builder@example.com',
    ]);

    NotificationDefinition::make('tests.builder')
        ->recipients([$user])
        ->title('Builder')
        ->body('Builder body')
        ->channels([NotificationChannel::Database])
        ->dispatch();

    expect($user->fresh()->notifications)->toHaveCount(1);
});
