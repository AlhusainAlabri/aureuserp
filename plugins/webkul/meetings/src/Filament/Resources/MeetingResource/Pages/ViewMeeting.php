<?php

namespace Webkul\Meetings\Filament\Resources\MeetingResource\Pages;

use App\Filament\Actions\ExportMeetingPdfAction;
use App\Filament\Traits\HasApprovalActions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use RuntimeException;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Models\Meeting;

class ViewMeeting extends ViewRecord
{
    use HasApprovalActions;

    protected static string $resource = MeetingResource::class;

    public function getTitle(): string
    {
        return __('meetings::meetings.pages.view_title', [
            'title' => $this->getRecord()->title,
        ]);
    }

    public function getSubheading(): ?string
    {
        if ($this->getRecord()->status !== 'archived') {
            return null;
        }

        return __('meetings::meetings.archived.read_only_notice');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('changeStatus')
                ->label(__('meetings::meetings.actions.change_status'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->modalHeading(__('meetings::meetings.actions.change_status'))
                ->modalDescription(__('meetings::meetings.actions.change_status_description'))
                ->visible(fn (): bool => auth()->user()?->can('updateStatus', $this->getRecord()) ?? false)
                ->schema([
                    Select::make('status')
                        ->label(__('meetings::meetings.fields.status'))
                        ->options(MeetingResource::statusOptions())
                        ->default(fn (): string => $this->getRecord()->status)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    try {
                        MeetingResource::applyStatusChange($this->getRecord(), $data['status']);

                        Notification::make()
                            ->success()
                            ->title(__('meetings::meetings.notifications.status_updated.title'))
                            ->send();
                    } catch (RuntimeException $exception) {
                        Notification::make()
                            ->danger()
                            ->title($exception->getMessage())
                            ->send();
                    }
                }),
            ChatterAction::make()
                ->setResource(static::$resource)
                ->label(__('chatter::filament/resources/actions/chatter-action.title'))
                ->hiddenLabel(false),
            ActionGroup::make([
                ...$this->getApprovalActions(),
                Action::make('confirm')
                    ->label(__('meetings::meetings.actions.confirm'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (?Meeting $record): bool => $record !== null && (auth()->user()?->can('confirm', $record) ?? false))
                    ->action(function (Meeting $record): void {
                        try {
                            $record->confirm();

                            Notification::make()
                                ->success()
                                ->title(__('meetings::meetings.notifications.confirm_success.title'))
                                ->send();
                        } catch (RuntimeException $exception) {
                            Notification::make()
                                ->danger()
                                ->title($exception->getMessage())
                                ->send();
                        }
                    }),
            ])
                ->label(__('meetings::meetings.action_groups.workflow'))
                ->icon('heroicon-o-arrow-path'),
            ActionGroup::make([
                ExportMeetingPdfAction::make(),
                Action::make('print')
                    ->label(__('meetings::meetings.actions.print'))
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->visible(fn (?Meeting $record): bool => $record !== null && in_array($record->status, ['approved', 'confirmed'], true))
                    ->modalContent(fn (Meeting $record) => view('meetings::meetings.print.meeting-minutes', [
                        'meeting' => $record->loadMissing([
                            'company',
                            'project',
                            'chairPerson',
                            'secretary',
                            'attendees.user',
                            'tasks.assignee',
                            'attachments',
                            'approvals.actions.user',
                        ]),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('meetings::meetings.actions.close')),
            ])
                ->label(__('meetings::meetings.action_groups.document'))
                ->icon('heroicon-o-document-text')
                ->visible(fn (?Meeting $record): bool => $record !== null && in_array($record->status, ['approved', 'confirmed'], true)),
            Action::make('archive')
                ->label(__('meetings::meetings.actions.archive'))
                ->icon('heroicon-o-archive-box')
                ->color('gray')
                ->visible(fn (?Meeting $record): bool => $record !== null && (auth()->user()?->can('archive', $record) ?? false))
                ->action(fn (Meeting $record): bool => $record->update(['status' => 'archived'])),
            EditAction::make()
                ->visible(fn (?Meeting $record): bool => $record !== null && $record->isDraft()),
        ];
    }
}
