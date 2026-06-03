<?php

namespace Webkul\Assets\Filament\Resources\AssetResource\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Assets\Filament\Resources\AssetResource;
use Webkul\Assets\Models\AssetBorrowing;

class BorrowingRelationManager extends RelationManager
{
    protected static string $relationship = 'borrowings';

    public static function getTitle($ownerRecord = null, ?string $pageClass = null): string
    {
        return __('assets::assets.relations.borrowings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['employee', 'borrowedBy', 'returnedBy']))
            ->columns([
                TextColumn::make('employee.name')
                    ->label(__('assets::assets.fields.employee'))
                    ->sortable(),
                TextColumn::make('borrowed_at')
                    ->label(__('assets::assets.fields.borrowed_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('due_at')
                    ->label(__('assets::assets.fields.due_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('returned_at')
                    ->label(__('assets::assets.fields.returned_at'))
                    ->dateTime()
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label(__('assets::assets.fields.borrowing_status'))
                    ->badge(),
                TextColumn::make('borrowedBy.name')
                    ->label(__('assets::assets.fields.processed_by'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('notes')
                    ->label(__('assets::assets.fields.notes'))
                    ->placeholder('—')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('borrowed_at', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->url(fn (AssetBorrowing $record): string => AssetResource::getUrl('view', ['record' => $record->asset_id])),
            ])
            ->emptyStateHeading(__('assets::assets.empty.no_borrowings'))
            ->emptyStateDescription(__('assets::assets.empty.no_borrowings_description'));
    }
}
