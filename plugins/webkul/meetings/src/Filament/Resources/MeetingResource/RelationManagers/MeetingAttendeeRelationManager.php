<?php

namespace Webkul\Meetings\Filament\Resources\MeetingResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rules\Unique;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Filament\Resources\MeetingResource\RelationManagers\Concerns\HasMeetingRelationCountBadge;
use Webkul\Meetings\Models\MeetingAttendee;

class MeetingAttendeeRelationManager extends RelationManager
{
    use HasMeetingRelationCountBadge;

    protected static string $relationship = 'attendees';

    public static function getTitle($ownerRecord = null, ?string $pageClass = null): string
    {
        return __('meetings::meetings.relations.attendees');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                MeetingResource::userSelect('user_id')
                    ->unique(
                        table: MeetingAttendee::class,
                        column: 'user_id',
                        modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('meeting_id', $this->getOwnerRecord()->id),
                        ignoreRecord: true,
                    )
                    ->validationMessages([
                        'unique' => __('meetings::meetings.validation.duplicate_attendee'),
                    ]),
                Select::make('role')
                    ->label(__('meetings::meetings.fields.role'))
                    ->options(MeetingResource::roleOptions())
                    ->default('member')
                    ->required(),
                Toggle::make('attended')
                    ->label(__('meetings::meetings.fields.attended')),
            ])
            ->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['user.partner']))
            ->columns([
                Stack::make([
                    Split::make([
                        ImageColumn::make('user.partner.avatar')
                            ->label('')
                            ->imageSize(56)
                            ->circular()
                            ->defaultImageUrl(fn (MeetingAttendee $record): string => MeetingResource::defaultUserAvatarUrl($record->user?->name)),
                        Stack::make([
                            TextColumn::make('user.name')
                                ->label(__('meetings::meetings.fields.user'))
                                ->weight(FontWeight::Bold)
                                ->searchable(),
                            TextColumn::make('role')
                                ->label(__('meetings::meetings.fields.role'))
                                ->formatStateUsing(fn (?string $state): string => MeetingResource::roleOptions()[$state] ?? (string) $state)
                                ->badge(),
                        ]),
                    ])->from('md'),
                    Split::make([
                        ToggleColumn::make('attended')
                            ->label(__('meetings::meetings.fields.attended'))
                            ->disabled(fn (): bool => ! (auth()->user()?->can('manageAttendees', $this->getOwnerRecord()) ?? false)),
                        IconColumn::make('signed')
                            ->label(__('meetings::meetings.fields.signed_at'))
                            ->icon(fn (MeetingAttendee $record): ?string => $record->hasSigned() ? 'heroicon-o-check-badge' : null)
                            ->color('success')
                            ->tooltip(fn (MeetingAttendee $record): ?string => $record->signed_at?->translatedFormat('d M Y H:i')),
                    ])->from('md'),
                ]),
            ])
            ->contentGrid([
                'md'  => 2,
                'xl'  => 3,
                '2xl' => 4,
            ])
            ->paginated(false)
            ->headerActions([
                Action::make('addChairPerson')
                    ->label(__('meetings::meetings.actions.add_chair_person'))
                    ->icon('heroicon-o-user-plus')
                    ->color('gray')
                    ->visible(fn (): bool => $this->canQuickAddUser($this->getOwnerRecord()->chair_person_id))
                    ->authorize(fn (): bool => auth()->user()?->can('manageAttendees', $this->getOwnerRecord()) ?? false)
                    ->action(function (): void {
                        $this->quickAddAttendee(
                            userId: $this->getOwnerRecord()->chair_person_id,
                            role: 'chair',
                        );
                    }),
                Action::make('addSecretary')
                    ->label(__('meetings::meetings.actions.add_secretary'))
                    ->icon('heroicon-o-user-plus')
                    ->color('gray')
                    ->visible(fn (): bool => $this->canQuickAddUser($this->getOwnerRecord()->secretary_id))
                    ->authorize(fn (): bool => auth()->user()?->can('manageAttendees', $this->getOwnerRecord()) ?? false)
                    ->action(function (): void {
                        $this->quickAddAttendee(
                            userId: $this->getOwnerRecord()->secretary_id,
                            role: 'secretary',
                        );
                    }),
                CreateAction::make()
                    ->label(__('meetings::meetings.actions.add_attendee'))
                    ->visible(fn (): bool => auth()->user()?->can('manageAttendees', $this->getOwnerRecord()) ?? false)
                    ->authorize(fn (): bool => auth()->user()?->can('manageAttendees', $this->getOwnerRecord()) ?? false),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('manageAttendees', $this->getOwnerRecord()) ?? false)
                    ->authorize(fn (): bool => auth()->user()?->can('manageAttendees', $this->getOwnerRecord()) ?? false),
                Action::make('sign')
                    ->label(__('meetings::meetings.actions.sign'))
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn (MeetingAttendee $record): bool => ! $record->hasSigned()
                        && (auth()->user()?->can('manageAttendees', $this->getOwnerRecord()) ?? false))
                    ->authorize(fn (MeetingAttendee $record): bool => auth()->user()?->can('manageAttendees', $this->getOwnerRecord()) ?? false)
                    ->action(fn (MeetingAttendee $record): bool => $record->update(['signed_at' => now()])),
                DeleteAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('manageAttendees', $this->getOwnerRecord()) ?? false)
                    ->authorize(fn (): bool => auth()->user()?->can('manageAttendees', $this->getOwnerRecord()) ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('mark_all_attended')
                        ->label(__('meetings::meetings.actions.mark_all_attended'))
                        ->icon('heroicon-o-check')
                        ->visible(fn (): bool => auth()->user()?->can('manageAttendees', $this->getOwnerRecord()) ?? false)
                        ->requiresConfirmation()
                        ->action(fn (Collection $records): int => $records->each->update(['attended' => true])->count()),
                ]),
            ])
            ->emptyStateHeading(__('meetings::meetings.empty.no_attendees'))
            ->emptyStateDescription(__('meetings::meetings.empty.no_attendees_description'));
    }

    protected function canQuickAddUser(?int $userId): bool
    {
        if ($userId === null) {
            return false;
        }

        if ($this->getOwnerRecord()->status === 'archived') {
            return false;
        }

        if (! (auth()->user()?->can('manageAttendees', $this->getOwnerRecord()) ?? false)) {
            return false;
        }

        return ! $this->getOwnerRecord()->attendees()->where('user_id', $userId)->exists();
    }

    protected function quickAddAttendee(int $userId, string $role): void
    {
        $this->getOwnerRecord()->attendees()->create([
            'user_id'  => $userId,
            'role'     => $role,
            'attended' => false,
        ]);

        Notification::make()
            ->success()
            ->title(__('meetings::meetings.notifications.attendee_added.title'))
            ->send();
    }
}
