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
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Models\MeetingAttendee;
use Webkul\Security\Models\User;

class MeetingAttendeeRelationManager extends RelationManager
{
    protected static string $relationship = 'attendees';

    public static function getTitle($ownerRecord = null, ?string $pageClass = null): string
    {
        return __('meetings::meetings.relations.attendees');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label(__('meetings::meetings.fields.user'))
                    ->options(fn () => User::query()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
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
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('meetings::meetings.fields.user'))
                    ->searchable(),
                TextColumn::make('role')
                    ->label(__('meetings::meetings.fields.role'))
                    ->formatStateUsing(fn (?string $state): string => MeetingResource::roleOptions()[$state] ?? (string) $state)
                    ->badge(),
                ToggleColumn::make('attended')
                    ->label(__('meetings::meetings.fields.attended')),
                TextColumn::make('signed_at')
                    ->label(__('meetings::meetings.fields.signed_at'))
                    ->dateTime()
                    ->placeholder('-'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('manageAttendees', $this->getOwnerRecord()) ?? false),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('manageAttendees', $this->getOwnerRecord()) ?? false),
                Action::make('sign')
                    ->label(__('meetings::meetings.actions.sign'))
                    ->icon('heroicon-o-pencil-square')
                    ->action(fn (MeetingAttendee $record): bool => $record->update(['signed_at' => now()])),
                DeleteAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('manageAttendees', $this->getOwnerRecord()) ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('mark_all_attended')
                        ->label(__('meetings::meetings.actions.mark_all_attended'))
                        ->icon('heroicon-o-check')
                        ->action(fn (Collection $records): int => $records->each->update(['attended' => true])->count()),
                ]),
            ]);
    }
}
