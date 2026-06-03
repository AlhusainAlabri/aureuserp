<?php

namespace Webkul\MyNotes\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Support\Htmlable;
use Webkul\MyNotes\Filament\Pages\MyNotesPage;
use Webkul\MyNotes\Models\Note;

class UpcomingRemindersWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public static function getHeading(): string|Htmlable|null
    {
        return __('my-notes::notes.widget.upcoming_reminders');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Note::query()
                    ->upcomingReminders()
                    ->notArchived()
                    ->orderBy('reminder_at')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('auto_title')
                    ->label(__('my-notes::notes.form.fields.title'))
                    ->searchable(false)
                    ->html(),

                Tables\Columns\TextColumn::make('reminder_at')
                    ->label(__('my-notes::notes.form.fields.reminder_at'))
                    ->dateTime('D, d M Y · h:i A')
                    ->sortable()
                    ->since()
                    ->description(fn (Note $note): string => $note->reminder_at->diffForHumans()),

                Tables\Columns\ColorColumn::make('color_hex')
                    ->label(__('my-notes::notes.form.fields.color'))
                    ->copyable(false),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label(__('my-notes::notes.actions.view'))
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn () => MyNotesPage::getUrl())
                    ->openUrlInNewTab(false),
            ])
            ->emptyStateHeading(__('my-notes::notes.widget.no_upcoming_reminders'))
            ->emptyStateIcon('heroicon-o-bell-slash')
            ->paginated(false);
    }
}
