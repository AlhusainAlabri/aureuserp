<?php

namespace Webkul\Meetings\Filament\Resources\MeetingResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MeetingAttachmentRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    public static function getTitle($ownerRecord = null, ?string $pageClass = null): string
    {
        return __('meetings::meetings.relations.attachments');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('file_path')
                    ->label(__('meetings::meetings.fields.file'))
                    ->disk('private')
                    ->directory(fn () => 'meetings/'.now()->year)
                    ->required()
                    ->afterStateUpdated(function ($state, callable $set): void {
                        if (! $state) {
                            return;
                        }

                        $set('file_name', basename((string) $state));
                        $set('file_size', 0);
                        $set('mime_type', 'application/octet-stream');
                    }),
                Hidden::make('file_name')->default('file'),
                Hidden::make('file_size')->default(0),
                Hidden::make('mime_type')->default('application/octet-stream'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('file_name')
                    ->label(__('meetings::meetings.fields.file_name'))
                    ->searchable(),
                TextColumn::make('file_size')
                    ->label(__('meetings::meetings.fields.file_size')),
                TextColumn::make('mime_type')
                    ->label(__('meetings::meetings.fields.mime_type')),
                TextColumn::make('creator.name')
                    ->label(__('meetings::meetings.fields.creator')),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
