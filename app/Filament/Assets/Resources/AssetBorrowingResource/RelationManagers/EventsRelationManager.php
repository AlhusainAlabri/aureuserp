<?php

namespace App\Filament\Assets\Resources\AssetBorrowingResource\RelationManagers;

use App\Support\SchemaMemo;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('assets-extensions::audit.title');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                if (! SchemaMemo::hasTable('asset_borrowing_events')) {
                    return $query->whereRaw('1 = 0');
                }

                return $query->with('actor')->orderByDesc('created_at');
            })
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('assets-extensions::audit.at'))
                    ->dateTime('d M Y H:i'),
                TextColumn::make('event_type')
                    ->label(__('assets-extensions::audit.event'))
                    ->badge(),
                TextColumn::make('actor.name')
                    ->label(__('assets-extensions::audit.actor'))
                    ->placeholder('—'),
                TextColumn::make('ip_address')
                    ->label(__('assets-extensions::audit.ip'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->paginated([10, 25])
            ->defaultSort('created_at', 'desc');
    }
}
