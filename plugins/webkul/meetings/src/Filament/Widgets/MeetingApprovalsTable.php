<?php

namespace Webkul\Meetings\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Models\Meeting;
use Wezlo\FilamentApproval\Services\ApprovalEngine;

class MeetingApprovalsTable extends TableWidget
{
    protected int|string|array $columnSpan = 5;

    protected ?string $pollingInterval = '60s';

    public function getHeading(): string
    {
        return __('meetings::meetings.dashboard.sections.my_approvals');
    }

    protected function getTableQuery(): Builder
    {
        return Meeting::query()
            ->whereHas('approvals.stepInstances', fn (Builder $query): Builder => $query
                ->where('status', 'waiting')
                ->whereJsonContains('assigned_approver_ids', auth()->id()))
            ->latest()
            ->limit(8);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('meeting_number')->label(__('meetings::meetings.fields.meeting_number')),
            TextColumn::make('title')->label(__('meetings::meetings.fields.title'))->wrap(),
            TextColumn::make('creator.name')->label(__('meetings::meetings.fields.creator')),
        ];
    }

    protected function getTableRecordActions(): array
    {
        return [
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
        ];
    }
}
