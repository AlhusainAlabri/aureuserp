<?php

namespace App\Services\Projects;

use App\Mail\Tasks\TaskDeadlineReminderMail;
use App\Models\Projects\UserTaskReminderPreference;
use App\Services\Notifications\NotificationChannel;
use App\Services\Notifications\NotificationDefinition;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Webkul\Project\Enums\TaskState;
use Webkul\Project\Models\Task;
use Webkul\Security\Models\User;

class TaskNotificationService
{
    public function __construct(
        private readonly NotificationDispatcher $notifications,
    ) {}

    public function notifyNewAssignment(Task $task): void
    {
        if (! Schema::hasTable('projects_task_users')) {
            return;
        }

        $task->loadMissing('users');

        foreach ($task->users as $user) {
            if ($user->id === auth()->id()) {
                continue;
            }

            $this->dispatchDatabaseOnly(
                key: 'tasks.assigned',
                recipient: $user,
                title: __('tasks.notifications.assigned.title'),
                body: __('tasks.notifications.assigned.body', ['title' => $task->title]),
            );
        }
    }

    public function notifyStatusChange(Task $task): void
    {
        $recipients = $this->resolveStakeholders($task);

        foreach ($recipients as $user) {
            if ($user->id === auth()->id()) {
                continue;
            }

            $this->dispatchDatabaseOnly(
                key: 'tasks.status_changed',
                recipient: $user,
                title: __('tasks.notifications.status_changed.title'),
                body: __('tasks.notifications.status_changed.body', [
                    'title'  => $task->title,
                    'status' => TaskStatePresenter::label($task->state),
                ]),
            );
        }
    }

    public function notifyDeadlineReminders(): int
    {
        if (! Schema::hasTable('projects_tasks')) {
            return 0;
        }

        $sent = 0;

        Task::query()
            ->whereNull('parent_id')
            ->whereNotIn('state', [TaskState::DONE, TaskState::CANCELLED])
            ->whereNotNull('deadline')
            ->with('users')
            ->chunkById(100, function (Collection $tasks) use (&$sent): void {
                foreach ($tasks as $task) {
                    $sent += $this->notifyTaskDeadlineIfDue($task);
                }
            });

        return $sent;
    }

    protected function notifyTaskDeadlineIfDue(Task $task): int
    {
        if ($task->deadline === null) {
            return 0;
        }

        $daysUntilDue = now()->startOfDay()->diffInDays($task->deadline->copy()->startOfDay(), false);
        $sent = 0;

        foreach ($this->resolveStakeholders($task) as $user) {
            $preferences = $this->resolvePreferences($user);

            if (! in_array($daysUntilDue, $preferences->reminderOffsetsInDays(), true)) {
                continue;
            }

            $isOverdue = $daysUntilDue < 0;
            $title = $isOverdue
                ? __('tasks.notifications.overdue.title')
                : __('tasks.notifications.deadline.title');
            $body = $isOverdue
                ? __('tasks.notifications.overdue.body', ['title' => $task->title])
                : __('tasks.notifications.deadline.body', [
                    'title' => $task->title,
                    'date'  => $task->deadline->format('d M Y'),
                ]);

            $definition = NotificationDefinition::make($isOverdue ? 'tasks.deadline.overdue' : 'tasks.deadline.reminder')
                ->recipients([$user])
                ->title($title)
                ->body($body)
                ->icon('heroicon-o-clipboard-document-check')
                ->channels([NotificationChannel::Database, NotificationChannel::Mail])
                ->databaseWhen(fn (User $recipient): bool => $preferences->database_enabled)
                ->emailWhen(fn (User $recipient): bool => $preferences->email_enabled && filled($recipient->email))
                ->mail(fn (User $recipient): TaskDeadlineReminderMail => new TaskDeadlineReminderMail($task, $recipient, $daysUntilDue))
                ->build();

            $channelsSent = 0;

            if ($preferences->database_enabled) {
                $channelsSent++;
            }

            if ($preferences->email_enabled && filled($user->email)) {
                $channelsSent++;
            }

            if ($channelsSent === 0) {
                continue;
            }

            $this->notifications->dispatch($definition);
            $sent += $channelsSent;
        }

        return $sent;
    }

    /** @return Collection<int, User> */
    protected function resolveStakeholders(Task $task): Collection
    {
        $users = collect($task->users ?? []);

        if (Schema::hasColumn('projects_tasks', 'owner_id') && $task->owner_id) {
            $owner = User::query()->find($task->owner_id);

            if ($owner instanceof User) {
                $users->push($owner);
            }
        }

        return $users->unique('id')->values();
    }

    protected function resolvePreferences(User $user): UserTaskReminderPreference
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

    protected function dispatchDatabaseOnly(string $key, User $recipient, string $title, string $body): void
    {
        $this->notifications->dispatch(
            NotificationDefinition::make($key)
                ->recipients([$recipient])
                ->title($title)
                ->body($body)
                ->icon('heroicon-o-clipboard-document-check')
                ->channels([NotificationChannel::Database])
                ->build(),
        );
    }
}
