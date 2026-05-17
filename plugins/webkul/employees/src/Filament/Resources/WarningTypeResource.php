<?php

namespace Webkul\Employee\Filament\Resources;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Webkul\Employee\Filament\Resources\WarningTypeResource\Pages\CreateWarningType;
use Webkul\Employee\Filament\Resources\WarningTypeResource\Pages\EditWarningType;
use Webkul\Employee\Filament\Resources\WarningTypeResource\Pages\ListWarningTypes;
use Webkul\Employee\Filament\Resources\WarningTypeResource\Pages\ViewWarningType;
use Webkul\Employee\Models\WarningType;

class WarningTypeResource extends Resource
{
    protected static ?string $model = WarningType::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('employees::filament/resources/warning-type.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('employees::filament/resources/warning-type.navigation.group');
    }

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make(__('employees::filament/resources/warning-type.form.sections.general.title'))
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('employees::filament/resources/warning-type.form.sections.general.fields.name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true),
                                Textarea::make('description')
                                    ->label(__('employees::filament/resources/warning-type.form.sections.general.fields.description'))
                                    ->nullable()
                                    ->maxLength(500)
                                    ->rows(3),
                            ])
                            ->columns(2)->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    TextColumn::make('name')
                        ->label(__('employees::filament/resources/warning-type.table.columns.name'))
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('description')
                        ->label(__('employees::filament/resources/warning-type.table.columns.description'))
                        ->limit(60)
                        ->color('gray'),
                    TextColumn::make('warnings_count')
                        ->label(__('employees::filament/resources/warning-type.table.columns.warnings-count'))
                        ->counts('warnings')
                        ->badge()
                        ->color('info'),
                ])->space(1),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->groups([
                Tables\Grouping\Group::make('name')
                    ->label(__('employees::filament/resources/warning-type.table.groups.name'))
                    ->collapsible(),
                Tables\Grouping\Group::make('created_at')
                    ->label(__('employees::filament/resources/warning-type.table.groups.created-at'))
                    ->date()
                    ->collapsible(),
                Tables\Grouping\Group::make('updated_at')
                    ->label(__('employees::filament/resources/warning-type.table.groups.updated-at'))
                    ->date()
                    ->collapsible(),
            ])
            ->filtersFormColumns(2)
            ->filters([
                QueryBuilder::make()
                    ->constraintPickerColumns(2)
                    ->constraints([
                        TextConstraint::make('name')
                            ->label(__('employees::filament/resources/warning-type.table.filters.name'))
                            ->icon('heroicon-o-flag'),
                        TextConstraint::make('description')
                            ->label(__('employees::filament/resources/warning-type.table.filters.description'))
                            ->icon('heroicon-o-document-text'),
                        DateConstraint::make('created_at')
                            ->label(__('employees::filament/resources/warning-type.table.filters.created-at')),
                        DateConstraint::make('updated_at')
                            ->label(__('employees::filament/resources/warning-type.table.filters.updated-at')),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('employees::filament/resources/warning-type.table.actions.delete.notification.title'))
                            ->body(__('employees::filament/resources/warning-type.table.actions.delete.notification.body')),
                    ),
                ActionGroup::make([
                    RestoreAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('employees::filament/resources/warning-type.table.actions.restore.notification.title'))
                                ->body(__('employees::filament/resources/warning-type.table.actions.restore.notification.body')),
                        ),
                    ForceDeleteAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('employees::filament/resources/warning-type.table.actions.force-delete.notification.title'))
                                ->body(__('employees::filament/resources/warning-type.table.actions.force-delete.notification.body')),
                        ),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    RestoreBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('employees::filament/resources/warning-type.table.bulk-actions.restore.notification.title'))
                                ->body(__('employees::filament/resources/warning-type.table.bulk-actions.restore.notification.body')),
                        ),
                    DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('employees::filament/resources/warning-type.table.bulk-actions.delete.notification.title'))
                                ->body(__('employees::filament/resources/warning-type.table.bulk-actions.delete.notification.body')),
                        ),
                    ForceDeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('employees::filament/resources/warning-type.table.bulk-actions.force-delete.notification.title'))
                                ->body(__('employees::filament/resources/warning-type.table.bulk-actions.force-delete.notification.body')),
                        ),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make(__('employees::filament/resources/warning-type.infolist.sections.general.title'))
                            ->schema([
                                TextEntry::make('name')
                                    ->placeholder('—')
                                    ->label(__('employees::filament/resources/warning-type.infolist.sections.general.entries.name')),
                                TextEntry::make('description')
                                    ->placeholder('—')
                                    ->label(__('employees::filament/resources/warning-type.infolist.sections.general.entries.description')),
                                TextEntry::make('warnings_count')
                                    ->placeholder('—')
                                    ->label(__('employees::filament/resources/warning-type.infolist.sections.general.entries.warnings-count'))
                                    ->state(fn (Model $record): int => $record->warnings()->count())
                                    ->badge()
                                    ->color('info'),
                            ])
                            ->columns(2)->columnSpanFull(),
                    ])
                    ->columnSpan('full'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListWarningTypes::route('/'),
            'create' => CreateWarningType::route('/create'),
            'edit'   => EditWarningType::route('/{record}/edit'),
            'view'   => ViewWarningType::route('/{record}'),
        ];
    }
}
