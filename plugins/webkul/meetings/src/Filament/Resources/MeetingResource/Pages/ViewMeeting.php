<?php

namespace Webkul\Meetings\Filament\Resources\MeetingResource\Pages;

use App\Filament\Actions\ExportMeetingPdfAction;
use App\Filament\Traits\HasApprovalActions;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
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

    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->setResource(static::$resource),
            ...$this->getApprovalActions(),
            Action::make('confirm')
                ->label(__('meetings::meetings.actions.confirm'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (Meeting $record): bool => auth()->user()?->can('confirm', $record) ?? false)
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
            ExportMeetingPdfAction::make(),
            Action::make('archive')
                ->label(__('meetings::meetings.actions.archive'))
                ->icon('heroicon-o-archive-box')
                ->color('gray')
                ->visible(fn (Meeting $record): bool => auth()->user()?->can('archive', $record) ?? false)
                ->action(fn (Meeting $record): bool => $record->update(['status' => 'archived'])),
            EditAction::make()
                ->visible(fn (Meeting $record): bool => $record->isDraft()),
        ];
    }
}
