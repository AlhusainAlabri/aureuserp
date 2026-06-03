<?php

namespace App\Filament\Assets\Resources\AssetBorrowingResource\RelationManagers;

use App\Models\Assets\AssetBorrowingEvent;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('assets-extensions::audit.title');
    }

    public function table(Table $table): Table
    {
        if (! Schema::hasTable('asset_borrowing_events')) {
            return $table->query(AssetBorrowingEvent::query()->whereRaw('1 = 0'))->columns([]);
        }

        return $table
            ->query(
                AssetBorrowingEvent::query()
                    ->where('asset_borrowing_id', $this->getOwnerRecord()->id)
                    ->orderByDesc('created_at')
            )
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
