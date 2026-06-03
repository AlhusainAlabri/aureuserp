<?php

namespace App\Services\Projects;

use Webkul\Project\Enums\TaskState;

class TaskStatePresenter
{
    public static function defaultState(): TaskState
    {
        return TaskState::APPROVED;
    }

    public static function label(string|TaskState $state): string
    {
        if ($state instanceof TaskState) {
            return $state->getLabel();
        }

        return TaskState::tryFrom($state)?->getLabel() ?? $state;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return TaskState::options();
    }

    /** @return array<string, string> */
    public static function specColors(): array
    {
        return [
            TaskState::APPROVED->value         => 'warning',
            TaskState::IN_PROGRESS->value      => 'info',
            TaskState::CHANGE_REQUESTED->value => 'gray',
            TaskState::DONE->value             => 'success',
            TaskState::CANCELLED->value        => 'danger',
        ];
    }

    public static function color(string|TaskState $state): string
    {
        $value = $state instanceof TaskState ? $state->value : $state;

        return self::specColors()[$value] ?? 'gray';
    }

    public static function isClosed(string|TaskState $state): bool
    {
        $value = $state instanceof TaskState ? $state->value : $state;

        return in_array($value, [TaskState::DONE->value, TaskState::CANCELLED->value], true);
    }

    public static function isOverdue(object $task): bool
    {
        if (self::isClosed($task->state)) {
            return false;
        }

        return $task->deadline !== null && $task->deadline->isPast();
    }
}
