<?php

use App\Models\Projects\UserTaskReminderPreference;
use App\Models\UserNotificationPreference;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    if (! Schema::hasTable('user_notification_preferences')) {
        Artisan::call('migrate', [
            '--path'  => 'database/migrations/2026_08_25_161202_create_user_notification_preferences_table.php',
            '--force' => true,
        ]);
    }
});

it('resolves notification keys to configured categories', function (): void {
    $service = app(NotificationPreferenceService::class);

    expect($service->resolveCategory('tasks.assigned'))->toBe('tasks')
        ->and($service->resolveCategory('assets.borrowing.approved'))->toBe('assets')
        ->and($service->resolveCategory('hr.leave.substitute_request'))->toBe('hr')
        ->and($service->resolveCategory('unknown.key'))->toBeNull();
});

it('persists and loads per-category preferences for a user', function (): void {
    $user = User::factory()->create();
    $service = app(NotificationPreferenceService::class);

    $service->savePreferences($user, [
        'tasks' => [
            'database_enabled' => false,
            'email_enabled'    => true,
        ],
        'inventory' => [
            'database_enabled' => true,
            'email_enabled'    => false,
        ],
    ]);

    expect(UserNotificationPreference::query()->where('user_id', $user->id)->count())->toBe(2)
        ->and($service->isDatabaseEnabled($user, 'tasks.assigned'))->toBeFalse()
        ->and($service->isEmailEnabled($user, 'inventory.low_stock'))->toBeFalse()
        ->and($service->isEmailEnabled($user, 'tasks.assigned'))->toBeTrue();
});

it('keeps approval notifications enabled even when preferences are disabled', function (): void {
    $user = User::factory()->create(['email' => 'approver@example.com']);
    $service = app(NotificationPreferenceService::class);

    $service->savePreferences($user, [
        'approvals' => [
            'database_enabled' => false,
            'email_enabled'    => false,
        ],
    ]);

    expect($service->isDatabaseEnabled($user, 'approvals.requested'))->toBeTrue()
        ->and($service->isEmailEnabled($user, 'approvals.requested'))->toBeTrue();
});

it('combines task category preferences with task reminder channel toggles', function (): void {
    $user = User::factory()->create(['email' => 'tasks@example.com']);
    $service = app(NotificationPreferenceService::class);

    $service->savePreferences($user, [
        'tasks' => [
            'database_enabled' => true,
            'email_enabled'    => true,
        ],
    ]);

    UserTaskReminderPreference::query()->create([
        'user_id'                  => $user->id,
        'notify_same_day'          => true,
        'notify_one_day_before'    => true,
        'notify_three_days_before' => true,
        'notify_seven_days_before' => false,
        'email_enabled'            => false,
        'database_enabled'         => true,
    ]);

    expect($service->isDatabaseEnabled($user, 'tasks.deadline.reminder'))->toBeTrue()
        ->and($service->isEmailEnabled($user, 'tasks.deadline.reminder'))->toBeFalse()
        ->and($service->isDatabaseEnabled($user, 'tasks.assigned'))->toBeTrue()
        ->and($service->isEmailEnabled($user, 'tasks.assigned'))->toBeTrue();
});
