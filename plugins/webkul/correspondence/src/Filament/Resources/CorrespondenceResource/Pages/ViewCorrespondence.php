<?php

namespace Webkul\Correspondence\Filament\Resources\CorrespondenceResource\Pages;

use App\Filament\Actions\ExportCorrespondencePdfAction;
use App\Filament\Traits\HasApprovalActions;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use RuntimeException;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\Correspondence\Services\CorrespondenceTaskService;
use Webkul\Project\Models\Project;
use Webkul\Security\Models\User;

class ViewCorrespondence extends ViewRecord
{
    use HasApprovalActions;

    protected static string $resource = CorrespondenceResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->record instanceof Correspondence && $this->record->isIncoming()) {
            $this->record->markAsReadBy();
        }
    }

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
            Action::make('createTask')
                ->label(__('correspondence::correspondence.actions.create_task'))
                ->icon('heroicon-o-clipboard-document-check')
                ->visible(fn (): bool => CorrespondenceTaskService::isAvailable())
                ->schema([
                    TextInput::make('title')
                        ->label(__('correspondence::correspondence.tasks.title'))
                        ->required()
                        ->maxLength(255),
                    Textarea::make('description')
                        ->label(__('correspondence::correspondence.body'))
                        ->columnSpanFull(),
                    Select::make('assignee_id')
                        ->label(__('correspondence::correspondence.tasks.assignee'))
                        ->options(fn (): array => User::query()->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    DateTimePicker::make('deadline')
                        ->label(__('correspondence::correspondence.tasks.deadline'))
                        ->native(false),
                    Select::make('project_id')
                        ->label(__('correspondence::correspondence.project'))
                        ->options(fn (): array => Project::query()->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload(),
                ])
                ->action(function (Correspondence $record, array $data): void {
                    $data['project_id'] ??= $record->project_id;

                    $task = CorrespondenceTaskService::createFromCorrespondence($record, $data);

                    if (! $task) {
                        Notification::make()
                            ->danger()
                            ->title(__('correspondence::correspondence.exceptions.task_create_failed'))
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title(__('correspondence::correspondence.tasks.created'))
                        ->send();
                }),
            ExportCorrespondencePdfAction::make(),
            Action::make('archive')
                ->label(__('correspondence::correspondence.actions.archive'))
                ->icon('heroicon-o-archive-box')
                ->color('gray')
                ->visible(fn (Correspondence $record): bool => auth()->user()?->can('archive', $record) ?? false)
                ->action(fn (Correspondence $record): bool => $record->update(['status' => 'archived'])),
            Action::make('unarchive')
                ->label(__('correspondence::correspondence.actions.unarchive'))
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('success')
                ->visible(fn (Correspondence $record): bool => $record->status === 'archived'
                    && (auth()->user()?->can('archive', $record) ?? false))
                ->action(function (Correspondence $record): void {
                    $record->unarchive();

                    Notification::make()
                        ->success()
                        ->title(__('correspondence::correspondence.actions.unarchive'))
                        ->send();
                }),
            EditAction::make()
                ->visible(fn (Correspondence $record): bool => $record->status === 'draft'),
        ];
    }
}
