<?php

namespace Webkul\Employee\Filament\Clusters\Configurations\Resources;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Webkul\Employee\Filament\Clusters\Configurations;
use Webkul\Employee\Filament\Clusters\Configurations\Resources\WarningTypeResource\Pages\EditWarningType;
use Webkul\Employee\Filament\Clusters\Configurations\Resources\WarningTypeResource\Pages\ListWarningTypes;
use Webkul\Employee\Filament\Clusters\Configurations\Resources\WarningTypeResource\Pages\ViewWarningType;
use Webkul\Employee\Models\WarningType;

class WarningTypeResource extends Resource
{
    protected static ?string $model = WarningType::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string|\UnitEnum|null $navigationGroup = 'Employee';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = Configurations::class;

    public static function getModelLabel(): string
    {
        return __('employees::filament/clusters/configurations/resources/warning-type.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('employees::filament/clusters/configurations/resources/warning-type.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('employees::filament/clusters/configurations/resources/warning-type.navigation.title');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    TextInput::make('name')
                        ->label(__('employees::filament/clusters/configurations/resources/warning-type.form.sections.general.fields.name'))
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255)
                        ->live(onBlur: true),
                    Textarea::make('description')
                        ->label(__('employees::filament/clusters/configurations/resources/warning-type.form.sections.general.fields.description'))
                        ->nullable()
                        ->maxLength(500)
                        ->rows(3),
                ])->columns(2)->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    TextColumn::make('name')
                        ->label(__('employees::filament/clusters/configurations/resources/warning-type.table.columns.name'))
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('description')
                        ->label(__('employees::filament/clusters/configurations/resources/warning-type.table.columns.description'))
                        ->limit(60)
                        ->color('gray'),
                    TextColumn::make('warnings_count')
                        ->label(__('employees::filament/clusters/configurations/resources/warning-type.table.columns.warnings-count'))
                        ->counts('warnings')
                        ->badge()
                        ->color('info'),
                ])->space(1),
            ])
            ->recordUrl(
                fn (WarningType $record): string => static::getUrl('view', ['record' => $record]),
            )
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->groups([
                Group::make('name')
                    ->label(__('employees::filament/clusters/configurations/resources/warning-type.table.groups.name'))
                    ->collapsible(),
                Group::make('created_at')
                    ->label(__('employees::filament/clusters/configurations/resources/warning-type.table.groups.created-at'))
                    ->date()
                    ->collapsible(),
                Group::make('updated_at')
                    ->label(__('employees::filament/clusters/configurations/resources/warning-type.table.groups.updated-at'))
                    ->date()
                    ->collapsible(),
            ])
            ->filtersFormColumns(2)
            ->filters([
                QueryBuilder::make()
                    ->constraintPickerColumns(2)
                    ->constraints([
                        TextConstraint::make('name')
                            ->label(__('employees::filament/clusters/configurations/resources/warning-type.table.filters.name'))
                            ->icon('heroicon-o-flag'),
                        TextConstraint::make('description')
                            ->label(__('employees::filament/clusters/configurations/resources/warning-type.table.filters.description'))
                            ->icon('heroicon-o-document-text'),
                        DateConstraint::make('created_at')
                            ->label(__('employees::filament/clusters/configurations/resources/warning-type.table.filters.created-at')),
                        DateConstraint::make('updated_at')
                            ->label(__('employees::filament/clusters/configurations/resources/warning-type.table.filters.updated-at')),
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('employees::filament/clusters/configurations/resources/warning-type.table.actions.delete.notification.title'))
                                ->body(__('employees::filament/clusters/configurations/resources/warning-type.table.actions.delete.notification.body')),
                        ),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('employees::filament/clusters/configurations/resources/warning-type.table.bulk-actions.delete.notification.title'))
                                ->body(__('employees::filament/clusters/configurations/resources/warning-type.table.bulk-actions.delete.notification.body')),
                        ),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus-circle')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('employees::filament/clusters/configurations/resources/warning-type.table.empty-state-actions.create.notification.title'))
                            ->body(__('employees::filament/clusters/configurations/resources/warning-type.table.empty-state-actions.create.notification.body')),
                    )
                    ->after(function ($record) {
                        return redirect(
                            self::getUrl('edit', ['record' => $record]),
                        );
                    }),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextEntry::make('name')
                            ->placeholder('—')
                            ->label(__('employees::filament/clusters/configurations/resources/warning-type.infolist.sections.general.entries.name')),
                        TextEntry::make('description')
                            ->placeholder('—')
                            ->label(__('employees::filament/clusters/configurations/resources/warning-type.infolist.sections.general.entries.description')),
                        TextEntry::make('warnings_count')
                            ->placeholder('—')
                            ->label(__('employees::filament/clusters/configurations/resources/warning-type.infolist.sections.general.entries.warnings-count'))
                            ->state(fn (Model $record): int => $record->warnings()->count())
                            ->badge()
                            ->color('info'),
                    ])->columns(2)->columnSpanFull(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWarningTypes::route('/'),
            'view'  => ViewWarningType::route('/{record}'),
            'edit'  => EditWarningType::route('/{record}/edit'),
        ];
    }
}
