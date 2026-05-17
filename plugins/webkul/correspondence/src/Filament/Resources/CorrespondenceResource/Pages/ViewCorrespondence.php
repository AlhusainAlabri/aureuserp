<?php

namespace Webkul\Correspondence\Filament\Resources\CorrespondenceResource\Pages;

use App\Filament\Actions\ExportCorrespondencePdfAction;
use App\Filament\Traits\HasApprovalActions;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use RuntimeException;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource;
use Webkul\Correspondence\Models\Correspondence;

class ViewCorrespondence extends ViewRecord
{
    use HasApprovalActions;

    protected static string $resource = CorrespondenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->setResource(static::$resource),
            ...$this->getApprovalActions(),
            Action::make('send')
                ->label(__('correspondence::correspondence.send_correspondence'))
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn (Correspondence $record): bool => auth()->user()?->can('send', $record) ?? false)
                ->action(function (Correspondence $record): void {
                    try {
                        $record->send();

                        Notification::make()
                            ->success()
                            ->title(__('correspondence::correspondence.email_sent'))
                            ->send();
                    } catch (RuntimeException $exception) {
                        Notification::make()
                            ->danger()
                            ->title($exception->getMessage())
                            ->send();
                    }
                }),
            Action::make('reply')
                ->label(__('correspondence::correspondence.reply'))
                ->icon('heroicon-o-arrow-uturn-left')
                ->url(fn (Correspondence $record): string => CorrespondenceResource::getUrl('create', ['reply_to' => $record->id])),
            ExportCorrespondencePdfAction::make(),
            Action::make('archive')
                ->label(__('correspondence::correspondence.actions.archive'))
                ->icon('heroicon-o-archive-box')
                ->color('gray')
                ->visible(fn (Correspondence $record): bool => auth()->user()?->can('archive', $record) ?? false)
                ->action(fn (Correspondence $record): bool => $record->update(['status' => 'archived'])),
            EditAction::make()
                ->visible(fn (Correspondence $record): bool => $record->status === 'draft'),
        ];
    }
}
