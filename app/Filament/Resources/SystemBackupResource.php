<?php

namespace App\Filament\Resources;

use App\Enums\Operations\BackupStatus;
use App\Enums\Operations\BackupType;
use App\Filament\Resources\SystemBackupResource\Pages\ListSystemBackups;
use App\Jobs\Operations\RestoreBackupJob;
use App\Jobs\Operations\RunBackupJob;
use App\Models\Operations\SystemBackup;
use App\Models\Operations\SystemOperationLog;
use App\Services\Operations\BackupRetentionService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class SystemBackupResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = SystemBackup::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?int $navigationSort = 2;

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'download',
            'delete',
            'restore',
        ];
    }

    public static function getNavigationGroup(): string
    {
        return __('operations.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('operations.backups.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('operations.backups.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('operations.backups.plural');
    }

    public static function canAccess(): bool
    {
        return SchemaFacade::hasTable('system_backups') && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('operations.backups.table.name'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('operations.backups.table.type'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('operations.backups.table.status'))
                    ->badge(),
                TextColumn::make('size_bytes')
                    ->label(__('operations.backups.table.size'))
                    ->formatStateUsing(fn (SystemBackup $record): string => $record->formattedSize()),
                TextColumn::make('is_automatic')
                    ->label(__('operations.backups.table.automatic'))
                    ->formatStateUsing(fn (bool $state): string => $state
                        ? __('operations.backups.automatic')
                        : __('operations.backups.manual')),
                TextColumn::make('completed_at')
                    ->label(__('operations.backups.table.completed_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('completed_at', 'desc')
            ->recordActions([
                Action::make('download')
                    ->label(__('operations.backups.actions.download'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (SystemBackup $record): string => route('operations.backups.download', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (SystemBackup $record): bool => auth()->user()?->can('download', $record) && $record->existsOnDisk()),
                Action::make('restore')
                    ->label(__('operations.backups.actions.restore'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('operations.backups.restore.heading'))
                    ->modalDescription(__('operations.backups.restore.description'))
                    ->visible(fn (SystemBackup $record): bool => auth()->user()?->can('restore', $record) && $record->status === BackupStatus::Completed)
                    ->action(function (SystemBackup $record): void {
                        RestoreBackupJob::dispatch($record->id, (int) auth()->id());

                        SystemOperationLog::record('backup.restore_queued', $record);

                        Notification::make()
                            ->title(__('operations.backups.restore.queued'))
                            ->success()
                            ->send();
                    }),
                DeleteAction::make()
                    ->action(function (SystemBackup $record): void {
                        app(BackupRetentionService::class)->delete($record);
                        SystemOperationLog::record('backup.deleted', $record);
                    }),
            ])
            ->toolbarActions([
                Action::make('createBackup')
                    ->label(__('operations.backups.actions.create'))
                    ->icon('heroicon-o-plus-circle')
                    ->schema([
                        Select::make('type')
                            ->label(__('operations.backups.table.type'))
                            ->options(BackupType::class)
                            ->required()
                            ->default(BackupType::Database),
                    ])
                    ->visible(fn (): bool => auth()->user()?->can('create', SystemBackup::class) ?? false)
                    ->action(function (array $data): void {
                        $type = $data['type'] instanceof BackupType
                            ? $data['type']
                            : BackupType::from($data['type']);

                        RunBackupJob::dispatch($type, false, auth()->id());

                        Notification::make()
                            ->title(__('operations.backups.queued'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSystemBackups::route('/'),
        ];
    }
}
