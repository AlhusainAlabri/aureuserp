<?php

namespace Webkul\Meetings\Filament\Resources\MeetingResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Filament\Resources\MeetingResource\RelationManagers\Concerns\HasMeetingRelationCountBadge;
use Webkul\Meetings\Models\MeetingTask;
use Webkul\Purchases\Models\PurchaseOrder;
use Webkul\Security\Models\User;

class MeetingTaskRelationManager extends RelationManager
{
    use HasMeetingRelationCountBadge;

    protected static string $relationship = 'tasks';

    public static function getTitle($ownerRecord = null, ?string $pageClass = null): string
    {
        return __('meetings::meetings.relations.tasks');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('meetings::meetings.fields.task_title'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(__('meetings::meetings.fields.description'))
                    ->columnSpanFull(),
                Select::make('assigned_to')
                    ->label(__('meetings::meetings.fields.assigned_to'))
                    ->options(fn (): array => $this->assigneeOptions())
                    ->helperText(__('meetings::meetings.form.assignee_hint'))
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('due_date')
                    ->label(__('meetings::meetings.fields.due_date'))
                    ->native(false),
                Select::make('priority')
                    ->label(__('meetings::meetings.fields.priority'))
                    ->options(MeetingResource::priorityOptions())
                    ->default('medium')
                    ->required(),
                Select::make('status')
                    ->label(__('meetings::meetings.fields.status'))
                    ->options(MeetingResource::taskStatusOptions())
                    ->default('pending')
                    ->required(),
                Select::make('purchase_request_id')
                    ->label(__('meetings::meetings.fields.purchase_request'))
                    ->options(MeetingResource::purchaseRequestOptions())
                    ->searchable()
                    ->visible(fn (): bool => class_exists(PurchaseOrder::class)),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('overdue')
                    ->label('')
                    ->icon(fn (MeetingTask $record): ?string => $record->isOverdue() ? 'heroicon-o-exclamation-triangle' : null)
                    ->color('danger'),
                TextColumn::make('title')
                    ->label(__('meetings::meetings.fields.task_title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('assignee.name')
                    ->label(__('meetings::meetings.fields.assigned_to'))
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label(__('meetings::meetings.fields.due_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('meetings::meetings.fields.status'))
                    ->formatStateUsing(fn (?string $state): string => MeetingResource::taskStatusOptions()[$state] ?? (string) $state)
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'completed'   => 'success',
                        'in_progress' => 'info',
                        'cancelled'   => 'gray',
                        default       => 'warning',
                    }),
                TextColumn::make('priority')
                    ->label(__('meetings::meetings.fields.priority'))
                    ->formatStateUsing(fn (?string $state): string => MeetingResource::priorityOptions()[$state] ?? (string) $state)
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'high'   => 'danger',
                        'medium' => 'warning',
                        default  => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('meetings::meetings.fields.status'))
                    ->options(MeetingResource::taskStatusOptions()),
                SelectFilter::make('assigned_to')
                    ->label(__('meetings::meetings.fields.assigned_to'))
                    ->options(fn () => User::query()->pluck('name', 'id')),
                Filter::make('overdue')
                    ->label(__('meetings::meetings.filters.overdue'))
                    ->query(fn (Builder $query): Builder => $query->overdue()),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('manageTasks', $this->getOwnerRecord()) ?? false)
                    ->authorize(fn (): bool => auth()->user()?->can('manageTasks', $this->getOwnerRecord()) ?? false),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('manageTasks', $this->getOwnerRecord()) ?? false)
                    ->authorize(fn (): bool => auth()->user()?->can('manageTasks', $this->getOwnerRecord()) ?? false),
                Action::make('complete')
                    ->label(__('meetings::meetings.actions.mark_complete'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (MeetingTask $record): bool => $record->status !== 'completed'
                        && (auth()->user()?->can('manageTasks', $this->getOwnerRecord()) ?? false))
                    ->authorize(fn (MeetingTask $record): bool => auth()->user()?->can('manageTasks', $this->getOwnerRecord()) ?? false)
                    ->action(function (MeetingTask $record): void {
                        $record->update([
                            'status'       => 'completed',
                            'completed_at' => now(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title(__('meetings::meetings.notifications.task_completed.title'))
                            ->send();
                    }),
                DeleteAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('manageTasks', $this->getOwnerRecord()) ?? false)
                    ->authorize(fn (): bool => auth()->user()?->can('manageTasks', $this->getOwnerRecord()) ?? false),
            ])
            ->emptyStateHeading(__('meetings::meetings.empty.no_meeting_tasks'))
            ->emptyStateDescription(__('meetings::meetings.empty.no_meeting_tasks_description'));
    }

    protected function assigneeOptions(): array
    {
        $attendeeIds = $this->getOwnerRecord()->attendees()->pluck('user_id')->all();

        return User::query()
            ->orderByRaw('case when id in ('.(count($attendeeIds) ? implode(',', $attendeeIds) : '0').') then 0 else 1 end')
            ->pluck('name', 'id')
            ->all();
    }
}
