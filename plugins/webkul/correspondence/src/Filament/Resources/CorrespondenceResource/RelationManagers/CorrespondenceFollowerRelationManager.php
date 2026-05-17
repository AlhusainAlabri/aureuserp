<?php

namespace Webkul\Correspondence\Filament\Resources\CorrespondenceResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Webkul\Security\Models\User;

class CorrespondenceFollowerRelationManager extends RelationManager
{
    protected static string $relationship = 'followers';

    public static function getTitle($ownerRecord = null, ?string $pageClass = null): string
    {
        return __('correspondence::correspondence.followers');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label(__('correspondence::correspondence.user'))
                    ->options(fn () => User::query()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label(__('correspondence::correspondence.user')),
                TextColumn::make('created_at')->label(__('correspondence::correspondence.date'))->dateTime(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('manageFollowers', $this->getOwnerRecord()) ?? false),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('manageFollowers', $this->getOwnerRecord()) ?? false),
            ]);
    }
}
