<?php

namespace App\Services\Projects;

use App\Mail\Tasks\TaskDeadlineReminderMail;
use App\Models\Projects\UserTaskReminderPreference;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Webkul\Project\Enums\TaskState;
use Webkul\Project\Models\Task;
use Webkul\Security\Models\User;

class TaskNotificationService
{
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

            $this->sendDatabaseNotification(
                $user,
                __('tasks.notifications.assigned.title'),
                __('tasks.notifications.assigned.body', ['title' => $task->title]),
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

            $this->sendDatabaseNotification(
                $user,
                __('tasks.notifications.status_changed.title'),
                __('tasks.notifications.status_changed.body', [
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

            if ($preferences->database_enabled) {
                $this->sendDatabaseNotification(
                    $user,
                    $daysUntilDue < 0
                        ? __('tasks.notifications.overdue.title')
                        : __('tasks.notifications.deadline.title'),
                    $daysUntilDue < 0
                        ? __('tasks.notifications.overdue.body', ['title' => $task->title])
                        : __('tasks.notifications.deadline.body', [
                            'title' => $task->title,
                            'date'  => $task->deadline->format('d M Y'),
                        ]),
                );
                $sent++;
            }

            if ($preferences->email_enabled && filled($user->email)) {
                Mail::to($user)->queue(new TaskDeadlineReminderMail($task, $user, $daysUntilDue));
                $sent++;
            }
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

    protected function sendDatabaseNotification(User $user, string $title, string $body): void
    {
        Notification::make()
            ->title($title)
            ->body($body)
            ->icon('heroicon-o-clipboard-document-check')
            ->sendToDatabase($user);
    }
}
