<?php

namespace Webkul\MyNotes\Livewire;

use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Webkul\MyNotes\Filament\Pages\MyNotesPage;
use Webkul\MyNotes\Models\Note;

class QuickNoteTopbar extends Component
{
    public string $quickBody = '';

    public function saveQuickNote(): void
    {
        $body = trim($this->quickBody);

        if ($body === '') {
            return;
        }

        Note::create(Note::normalizePayload([
            'type'  => 'text',
            'body'  => Note::wrapPlainTextAsHtml($body),
            'color' => 'default',
        ]));

        $this->quickBody = '';

        Notification::make()
            ->title(__('my-notes::notes.notifications.saved'))
            ->success()
            ->send();

        $this->dispatch('quick-note-saved');
    }

    public function createTypeUrl(string $type): string
    {
        if (! in_array($type, Note::TYPES, true)) {
            $type = 'text';
        }

        return MyNotesPage::getUrl(['create' => $type]);
    }

    public function notesIndexUrl(): string
    {
        return MyNotesPage::getUrl();
    }

    public function render(): View
    {
        return view('my-notes::livewire.quick-note-topbar');
    }
}
