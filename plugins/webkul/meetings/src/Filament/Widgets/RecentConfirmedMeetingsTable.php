<?php

namespace Webkul\Meetings\Filament\Widgets;

use App\Filament\Actions\ExportMeetingPdfAction;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Filament\Widgets\Concerns\HasMeetingVisibility;
use Webkul\Meetings\Filament\Widgets\Concerns\InteractsWithMeetingDashboardFilters;
use Webkul\Meetings\Models\Meeting;

class RecentConfirmedMeetingsTable extends TableWidget
{
    use HasMeetingVisibility;
    use InteractsWithMeetingDashboardFilters;
    use InteractsWithPageFilters;

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 5;

    protected ?string $pollingInterval = null;

    public function getTableHeading(): ?string
    {
        return __('meetings::meetings.dashboard.sections.recent_confirmed');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->filteredMeetingsQuery()
                    ->confirmed()
                    ->with('chairPerson')
                    ->latest('meeting_date')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('meeting_number')->label(__('meetings::meetings.fields.meeting_number')),
                TextColumn::make('title')->label(__('meetings::meetings.fields.title'))->wrap(),
                TextColumn::make('meeting_date')
                    ->label(__('meetings::meetings.fields.meeting_date'))
                    ->formatStateUsing(fn ($state, Meeting $record): string => $record->meeting_date?->translatedFormat('d M Y') ?? '-'),
                TextColumn::make('chairPerson.name')->label(__('meetings::meetings.fields.chair_person')),
            ])
            ->headerActions([
                Action::make('viewAllConfirmed')
                    ->label(__('meetings::meetings.dashboard.actions.view_all_confirmed'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(MeetingResource::getUrl('index', ['activeTab' => 'confirmed']))
                    ->color('gray'),
            ])
            ->recordActions([
                ViewAction::make()->url(fn (Meeting $record): string => MeetingResource::getUrl('view', ['record' => $record])),
                ExportMeetingPdfAction::make(),
            ])
            ->emptyStateHeading(__('meetings::meetings.empty.no_confirmed_meetings'))
            ->emptyStateDescription(__('meetings::meetings.empty.no_confirmed_meetings_description'))
            ->paginated(false);
    }
}
