<?php

namespace Webkul\Employee\Filament\Resources\SubmissionResource\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Webkul\Employee\Filament\Resources\SubmissionResource;
use Webkul\Employee\Mail\SubmissionReplyMail;

class ViewSubmission extends ViewRecord
{
    protected static string $resource = SubmissionResource::class;

    protected string $view = 'employees::filament.resources.submission-resource.pages.view-submission';

    public ?string $replyBody = null;

    public bool $replyInternal = false;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('changeStatus')
                ->label(__('employees::filament/resources/submission.pages.view-submission.actions.change-status'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->schema([
                    Select::make('status')
                        ->label(__('employees::filament/resources/submission.form.fields.status'))
                        ->options([
                            'open'         => __('employees::filament/resources/submission.statuses.open'),
                            'under_review' => __('employees::filament/resources/submission.statuses.under_review'),
                            'resolved'     => __('employees::filament/resources/submission.statuses.resolved'),
                            'closed'       => __('employees::filament/resources/submission.statuses.closed'),
                        ])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $updates = ['status' => $data['status']];
                    if ($data['status'] === 'resolved') {
                        $updates['resolved_at'] = now();
                    }
                    if ($data['status'] === 'closed') {
                        $updates['closed_at'] = now();
                    }
                    $this->getRecord()->update($updates);

                    Notification::make()
                        ->success()
                        ->title(__('employees::filament/resources/submission.pages.view-submission.notifications.status-updated.title'))
                        ->body(__('employees::filament/resources/submission.pages.view-submission.notifications.status-updated.body'))
                        ->send();
                }),

            Action::make('setPriority')
                ->label(__('employees::filament/resources/submission.pages.view-submission.actions.set-priority'))
                ->icon('heroicon-o-flag')
                ->color('gray')
                ->schema([
                    Select::make('priority')
                        ->label(__('employees::filament/resources/submission.form.fields.priority'))
                        ->options([
                            'low'    => __('employees::filament/resources/submission.priorities.low'),
                            'medium' => __('employees::filament/resources/submission.priorities.medium'),
                            'high'   => __('employees::filament/resources/submission.priorities.high'),
                        ])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->getRecord()->update(['priority' => $data['priority']]);

                    Notification::make()
                        ->success()
                        ->title(__('employees::filament/resources/submission.pages.view-submission.notifications.priority-updated.title'))
                        ->body(__('employees::filament/resources/submission.pages.view-submission.notifications.priority-updated.body'))
                        ->send();
                }),

            Action::make('delete')
                ->label(__('employees::filament/resources/submission.pages.view-submission.actions.delete'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->getRecord()->delete();
                    $this->redirect(ListSubmissions::getUrl());
                }),
        ];
    }

    public function sendReply(): void
    {
        $record = $this->getRecord();

        if (empty($this->replyBody)) {
            return;
        }

        $reply = $record->replies()->create([
            'body'        => $this->replyBody,
            'is_internal' => $this->replyInternal,
            'replied_by'  => Auth::id(),
        ]);

        if (! $this->replyInternal) {
            if ($record->employee?->work_email) {
                Mail::to($record->employee->work_email)->queue(
                    new SubmissionReplyMail($record, $reply)
                );
            }
        }

        $this->replyBody = '';
        $this->replyInternal = false;

        Notification::make()
            ->success()
            ->title(__('employees::filament/resources/submission.pages.view-submission.notifications.reply-sent.title'))
            ->body(__('employees::filament/resources/submission.pages.view-submission.notifications.reply-sent.body'))
            ->send();
    }

    public function markUnderReview(): void
    {
        $this->getRecord()->update(['status' => 'under_review']);
    }

    public function markResolved(): void
    {
        $this->getRecord()->update(['status' => 'resolved', 'resolved_at' => now()]);
    }

    public function closeTicket(): void
    {
        $this->getRecord()->update(['status' => 'closed', 'closed_at' => now()]);
    }
}
