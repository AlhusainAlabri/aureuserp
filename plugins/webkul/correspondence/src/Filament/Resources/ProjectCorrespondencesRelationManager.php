<?php

namespace Webkul\Correspondence\Filament\Resources;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectCorrespondencesRelationManager extends RelationManager
{
    protected static string $relationship = 'correspondences';

    public static function getTitle($ownerRecord = null, ?string $pageClass = null): string
    {
        return __('correspondence::correspondence.relations.project_correspondences');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')->label(__('correspondence::correspondence.reference_number')),
                TextColumn::make('subject')->label(__('correspondence::correspondence.subject'))->searchable(),
                TextColumn::make('direction')->label(__('correspondence::correspondence.direction'))->badge(),
                TextColumn::make('status')->label(__('correspondence::correspondence.status.label'))->badge(),
            ])
            ->recordActions([
                ViewAction::make()->url(fn ($record): string => CorrespondenceResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
