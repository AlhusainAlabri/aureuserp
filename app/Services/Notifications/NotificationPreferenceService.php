<?php

namespace App\Services\Notifications;

use App\Models\Projects\UserTaskReminderPreference;
use App\Models\UserNotificationPreference;
use Illuminate\Support\Facades\Schema;
use Webkul\Security\Models\User;

class NotificationPreferenceService
{
    /** @return array<string, array<string, mixed>> */
    public function categories(): array
    {
        return config('notifications.categories', []);
    }

    public function resolveCategory(string $notificationKey): ?string
    {
        foreach ($this->categories() as $category => $definition) {
            foreach ($definition['prefixes'] ?? [] as $prefix) {
                if (str_starts_with($notificationKey, $prefix)) {
                    return $category;
                }
            }
        }

        return null;
    }

    public function isCategoryRequired(string $category): bool
    {
        return (bool) ($this->categories()[$category]['required'] ?? false);
    }

    public function isDatabaseEnabled(User $user, string $notificationKey): bool
    {
        $category = $this->resolveCategory($notificationKey);

        if ($category === null) {
            return (bool) config('notifications.defaults.database_enabled', true);
        }

        if ($this->isCategoryRequired($category)) {
            return true;
        }

        $enabled = $this->resolveCategoryPreference($user, $category)?->database_enabled
            ?? config('notifications.defaults.database_enabled', true);

        if ($enabled && $this->usesTaskReminderPreferences($notificationKey)) {
            return $this->resolveTaskReminderPreference($user)->database_enabled;
        }

        return $enabled;
    }

    public function isEmailEnabled(User $user, string $notificationKey): bool
    {
        if (! filled($user->email)) {
            return false;
        }

        $category = $this->resolveCategory($notificationKey);

        if ($category === null) {
            return (bool) config('notifications.defaults.email_enabled', true);
        }

        if ($this->isCategoryRequired($category)) {
            return true;
        }

        $enabled = $this->resolveCategoryPreference($user, $category)?->email_enabled
            ?? config('notifications.defaults.email_enabled', true);

        if ($enabled && $this->usesTaskReminderPreferences($notificationKey)) {
            return $this->resolveTaskReminderPreference($user)->email_enabled;
        }

        return $enabled;
    }

    /**
     * @return array<string, array{database_enabled: bool, email_enabled: bool, required: bool}>
     */
    public function preferencesForUser(User $user): array
    {
        $stored = UserNotificationPreference::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('category');

        $preferences = [];

        foreach ($this->categories() as $category => $definition) {
            $record = $stored->get($category);

            $preferences[$category] = [
                'database_enabled' => $record?->database_enabled ?? config('notifications.defaults.database_enabled', true),
                'email_enabled'    => $record?->email_enabled ?? config('notifications.defaults.email_enabled', true),
                'required'         => (bool) ($definition['required'] ?? false),
            ];
        }

        return $preferences;
    }

    /**
     * @param  array<string, array{database_enabled?: bool, email_enabled?: bool}>  $preferences
     */
    public function savePreferences(User $user, array $preferences): void
    {
        foreach ($this->categories() as $category => $definition) {
            if ($this->isCategoryRequired($category)) {
                continue;
            }

            if (! array_key_exists($category, $preferences)) {
                continue;
            }

            UserNotificationPreference::query()->updateOrCreate(
                [
                    'user_id'  => $user->id,
                    'category' => $category,
                ],
                [
                    'database_enabled' => (bool) ($preferences[$category]['database_enabled'] ?? true),
                    'email_enabled'    => (bool) ($preferences[$category]['email_enabled'] ?? true),
                ],
            );
        }
    }

    /** @return array<string, bool> */
    public function taskReminderOffsetsForUser(User $user): array
    {
        $preference = $this->resolveTaskReminderPreference($user);

        return [
            'notify_same_day'          => $preference->notify_same_day,
            'notify_one_day_before'    => $preference->notify_one_day_before,
            'notify_three_days_before' => $preference->notify_three_days_before,
            'notify_seven_days_before' => $preference->notify_seven_days_before,
        ];
    }

    /** @param  array<string, bool>  $offsets */
    public function saveTaskReminderOffsets(User $user, array $offsets): void
    {
        if (! Schema::hasTable('user_task_reminder_preferences')) {
            return;
        }

        UserTaskReminderPreference::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'notify_same_day'          => (bool) ($offsets['notify_same_day'] ?? true),
                'notify_one_day_before'    => (bool) ($offsets['notify_one_day_before'] ?? true),
                'notify_three_days_before' => (bool) ($offsets['notify_three_days_before'] ?? true),
                'notify_seven_days_before' => (bool) ($offsets['notify_seven_days_before'] ?? false),
            ],
        );
    }

    protected function resolveCategoryPreference(User $user, string $category): ?UserNotificationPreference
    {
        return UserNotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('category', $category)
            ->first();
    }

    protected function usesTaskReminderPreferences(string $notificationKey): bool
    {
        return str_starts_with($notificationKey, 'tasks.deadline');
    }

    protected function resolveTaskReminderPreference(User $user): UserTaskReminderPreference
    {
        return UserTaskReminderPreference::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'notify_same_day'          => true,
                'notify_one_day_before'    => true,
                'notify_three_days_before' => true,
                'notify_seven_days_before' => false,
                'email_enabled'            => true,
                'database_enabled'         => true,
            ],
        );
    }
}
