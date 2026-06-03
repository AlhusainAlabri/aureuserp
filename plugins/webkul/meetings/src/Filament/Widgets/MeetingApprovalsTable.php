<?php

namespace Webkul\Meetings\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Filament\Widgets\Concerns\HasMeetingVisibility;
use Webkul\Meetings\Filament\Widgets\Concerns\InteractsWithMeetingDashboardFilters;
use Webkul\Meetings\Models\Meeting;
use Wezlo\FilamentApproval\Services\ApprovalEngine;

class MeetingApprovalsTable extends TableWidget
{
    use HasMeetingVisibility;
    use InteractsWithMeetingDashboardFilters;
    use InteractsWithPageFilters;

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 5;

    protected ?string $pollingInterval = null;

    public function getTableHeading(): ?string
    {
        return __('meetings::meetings.dashboard.sections.my_approvals');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->applyMeetingDashboardFilters(
                    Meeting::query()
                        ->whereHas('approvals.stepInstances', fn (Builder $query): Builder => $query
                            ->where('status', 'waiting')
                            ->whereJsonContains('assigned_approver_ids', auth()->id()))
                )
                    ->latest()
                    ->limit(8)
            )
            ->columns([
                TextColumn::make('meeting_number')->label(__('meetings::meetings.fields.meeting_number')),
                TextColumn::make('title')->label(__('meetings::meetings.fields.title'))->wrap(),
                TextColumn::make('creator.name')->label(__('meetings::meetings.fields.creator')),
            ])
            ->headerActions([
                Action::make('viewAllPending')
                    ->label(__('meetings::meetings.dashboard.actions.view_all_pending'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(MeetingResource::getUrl('index', ['activeTab' => 'pending_approval']))
                    ->color('gray'),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(__('meetings::meetings.actions.approve'))
                    ->color('success')
                    ->action(function (Meeting $record): void {
                        app(ApprovalEngine::class)->approve($record->currentApproval()->currentStepInstance(), auth()->id());
                    }),
                Action::make('reject')
                    ->label(__('meetings::meetings.actions.reject'))
                    ->color('danger')
                    ->schema([
                        Textarea::make('comment')
                            ->label(__('meetings::meetings.fields.rejection_reason'))
                            ->required(),
                    ])
                    ->action(function (Meeting $record, array $data): void {
                        app(ApprovalEngine::class)->reject($record->currentApproval()->currentStepInstance(), auth()->id(), $data['comment']);
                    }),
                Action::make('view')
                    ->label(__('meetings::meetings.actions.view'))
                    ->url(fn (Meeting $record): string => MeetingResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading(__('meetings::meetings.empty.no_approvals'))
            ->emptyStateDescription(__('meetings::meetings.empty.no_approvals_description'))
            ->paginated(false);
    }
}
