<?php

namespace Webkul\Meetings\Console;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Models\MeetingTask;

class NotifyOverdueMeetingTasks extends Command
{
    protected $signature = 'meetings:notify-overdue-tasks';

    protected $description = 'Notify assignees and chair persons about overdue meeting tasks.';

    public function handle(): int
    {
        MeetingTask::query()
            ->overdue()
            ->with(['assignee', 'meeting.chairPerson'])
            ->each(function (MeetingTask $task): void {
                $users = collect([$task->assignee, $task->meeting?->chairPerson])
                    ->filter()
                    ->unique('id');

                if ($users->isEmpty()) {
                    return;
                }

                Notification::make()
                    ->title(__('meetings::meetings.notifications.task_overdue.title'))
                    ->body(__('meetings::meetings.notifications.task_overdue.body', [
                        'title'  => $task->title,
                        'number' => $task->meeting?->meeting_number,
                    ]))
                    ->actions([
                        Action::make('view')
                            ->label(__('meetings::meetings.actions.view'))
                            ->url(MeetingResource::getUrl('view', ['record' => $task->meeting])),
                    ])
                    ->sendToDatabase($users);
            });

        $this->info(__('meetings::meetings.commands.overdue.done'));

        return self::SUCCESS;
    }
}
