<?php

namespace Webkul\MyNotes\Filament\Widgets;

use App\Support\FilamentUrl;
use Filament\Widgets\Widget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Webkul\MyNotes\Filament\Pages\MyNotesPage;
use Webkul\MyNotes\Models\Note;
use Webkul\MyNotes\Support\NoteDateFormatter;

class MyNotesBoardWidget extends Widget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'my-notes::widgets.my-notes-board-widget';

    public static function getHeading(): string|Htmlable|null
    {
        return __('my-notes::notes.widget.board_heading');
    }

    protected function getViewData(): array
    {
        return [
            'pinnedNotes'      => $this->getPinnedNotes(),
            'upcomingReminders'=> $this->getUpcomingReminders(),
            'notesUrl'         => class_exists(FilamentUrl::class)
                ? FilamentUrl::appendLocaleToUrl(MyNotesPage::getUrl())
                : MyNotesPage::getUrl(),
        ];
    }

    /** @return Collection<int, Note> */
    protected function getPinnedNotes(): Collection
    {
        return Note::query()
            ->notArchived()
            ->pinned()
            ->orderByDesc('updated_at')
            ->limit(3)
            ->get();
    }

    /** @return Collection<int, Note> */
    protected function getUpcomingReminders(): Collection
    {
        return Note::query()
            ->upcomingReminders()
            ->notArchived()
            ->orderBy('reminder_at')
            ->limit(3)
            ->get();
    }

    public function formatReminderAt(Note $note): string
    {
        return NoteDateFormatter::formatDateTime($note->reminder_at);
    }
}
