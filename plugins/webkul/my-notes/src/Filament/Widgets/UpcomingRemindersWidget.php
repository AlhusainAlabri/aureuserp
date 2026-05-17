<?php

namespace Webkul\MyNotes\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Contracts\Support\Htmlable;
use Webkul\MyNotes\Models\Note;

class UpcomingRemindersWidget extends Widget
{
    protected string $view = 'my-notes::widgets.upcoming-reminders';

    protected int|string|array $columnSpan = 'full';

    public static function getHeading(): string|Htmlable|null
    {
        return __('my-notes::notes.upcoming_reminders');
    }

    public function getRemindersProperty()
    {
        return Note::query()
            ->upcomingReminders()
            ->notArchived()
            ->orderBy('reminder_at')
            ->limit(3)
            ->get();
    }
}
