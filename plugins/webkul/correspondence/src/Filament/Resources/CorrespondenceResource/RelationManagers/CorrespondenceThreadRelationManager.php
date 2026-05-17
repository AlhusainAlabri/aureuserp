<?php

namespace Webkul\Correspondence\Filament\Resources\CorrespondenceResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource;
use Webkul\Correspondence\Models\Correspondence;

class CorrespondenceThreadRelationManager extends RelationManager
{
    protected static string $relationship = 'replies';

    public static function getTitle($ownerRecord = null, ?string $pageClass = null): string
    {
        return __('correspondence::correspondence.thread');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')->label(__('correspondence::correspondence.reference_number')),
                TextColumn::make('direction')
                    ->label(__('correspondence::correspondence.direction'))
                    ->formatStateUsing(fn (?string $state): string => CorrespondenceResource::directionOptions()[$state] ?? (string) $state)
                    ->badge(),
                TextColumn::make('created_at')->label(__('correspondence::correspondence.date'))->dateTime(),
                TextColumn::make('status')
                    ->label(__('correspondence::correspondence.status.label'))
                    ->formatStateUsing(fn (?string $state): string => CorrespondenceResource::statusOptions()[$state] ?? (string) $state)
                    ->badge(),
                TextColumn::make('subject')->label(__('correspondence::correspondence.subject')),
            ])
            ->recordActions([
                Action::make('view')
                    ->label(__('correspondence::correspondence.actions.view'))
                    ->url(fn (Correspondence $record): string => CorrespondenceResource::getUrl('view', ['record' => $record])),
                Action::make('reply')
                    ->label(__('correspondence::correspondence.reply'))
                    ->url(fn (Correspondence $record): string => CorrespondenceResource::getUrl('create', ['reply_to' => $record->id])),
            ]);
    }
}
