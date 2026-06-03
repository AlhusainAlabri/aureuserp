<?php

namespace Webkul\DocumentArchive\Filament\Resources\DocFolderResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;
use Webkul\Security\Models\User;

class FolderPermissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'permissions';

    public static function getTitle($ownerRecord = null, ?string $pageClass = null): string
    {
        return __('document-archive::document-archive.permissions.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label(__('document-archive::document-archive.permissions.type'))
                    ->options([
                        'user' => __('document-archive::document-archive.permissions.types.user'),
                        'role' => __('document-archive::document-archive.permissions.types.role'),
                    ])
                    ->required()
                    ->live(),
                Select::make('user_id')
                    ->label(__('document-archive::document-archive.permissions.user'))
                    ->options(fn () => User::query()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->visible(fn (callable $get): bool => $get('type') === 'user')
                    ->required(fn (callable $get): bool => $get('type') === 'user'),
                Select::make('role_name')
                    ->label(__('document-archive::document-archive.permissions.role'))
                    ->options(fn () => Role::query()->pluck('name', 'name'))
                    ->searchable()
                    ->preload()
                    ->visible(fn (callable $get): bool => $get('type') === 'role')
                    ->required(fn (callable $get): bool => $get('type') === 'role'),
                Select::make('permission')
                    ->label(__('document-archive::document-archive.permissions.permission'))
                    ->options([
                        'view'   => __('document-archive::document-archive.permissions.levels.view'),
                        'upload' => __('document-archive::document-archive.permissions.levels.upload'),
                        'manage' => __('document-archive::document-archive.permissions.levels.manage'),
                    ])
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label(__('document-archive::document-archive.permissions.type'))
                    ->badge(),
                TextColumn::make('user.name')
                    ->label(__('document-archive::document-archive.permissions.user'))
                    ->placeholder('-'),
                TextColumn::make('role_name')
                    ->label(__('document-archive::document-archive.permissions.role'))
                    ->placeholder('-'),
                TextColumn::make('permission')
                    ->label(__('document-archive::document-archive.permissions.permission'))
                    ->badge(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
