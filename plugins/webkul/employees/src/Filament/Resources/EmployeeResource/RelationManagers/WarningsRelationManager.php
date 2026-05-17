<?php

namespace Webkul\Employee\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Webkul\Employee\Mail\EmployeeWarningMail;

class WarningsRelationManager extends RelationManager
{
    protected static string $relationship = 'warnings';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    Hidden::make('creator_id')
                        ->default(fn () => Auth::id()),
                    Hidden::make('company_id')
                        ->default(fn ($livewire) => $livewire->getOwnerRecord()->company_id),
                    Select::make('warning_type_id')
                        ->label(__('employees::filament/resources/employee/relation-manager/warnings.form.fields.warning-type'))
                        ->relationship('warningType', 'name')
                        ->searchable()
                        ->preload()
                        ->placeholder(__('employees::filament/resources/employee/relation-manager/warnings.form.fields.warning-type-placeholder'))
                        ->nullable(),
                    TextInput::make('subject')
                        ->label(__('employees::filament/resources/employee/relation-manager/warnings.form.fields.subject'))
                        ->required()
                        ->maxLength(255),
                    Textarea::make('description')
                        ->label(__('employees::filament/resources/employee/relation-manager/warnings.form.fields.description'))
                        ->nullable()
                        ->maxLength(2000)
                        ->rows(3),
                    DatePicker::make('issued_at')
                        ->label(__('employees::filament/resources/employee/relation-manager/warnings.form.fields.issued-at'))
                        ->required()
                        ->native(false)
                        ->suffixIcon('heroicon-o-calendar')
                        ->default(now()),
                    DatePicker::make('effective_date')
                        ->label(__('employees::filament/resources/employee/relation-manager/warnings.form.fields.effective-date'))
                        ->native(false)
                        ->suffixIcon('heroicon-o-calendar')
                        ->nullable(),
                    DatePicker::make('expiry_date')
                        ->label(__('employees::filament/resources/employee/relation-manager/warnings.form.fields.expiry-date'))
                        ->native(false)
                        ->suffixIcon('heroicon-o-calendar')
                        ->nullable(),
                    Toggle::make('is_acknowledged')
                        ->label(__('employees::filament/resources/employee/relation-manager/warnings.form.fields.is-acknowledged'))
                        ->live()
                        ->default(false),
                    Hidden::make('acknowledged_at')
                        ->default(fn (callable $get) => $get('is_acknowledged') ? now() : null),
                    Hidden::make('acknowledged_by')
                        ->default(fn (callable $get) => $get('is_acknowledged') ? Auth::id() : null),
                ])->columns(2)->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('warningType.name')
                    ->label(__('employees::filament/resources/employee/relation-manager/warnings.table.columns.warning-type'))
                    ->badge()
                    ->color('primary')
                    ->placeholder('—'),
                TextColumn::make('subject')
                    ->label(__('employees::filament/resources/employee/relation-manager/warnings.table.columns.subject'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('issued_at')
                    ->label(__('employees::filament/resources/employee/relation-manager/warnings.table.columns.issued-at'))
                    ->date()
                    ->sortable(),
                TextColumn::make('effective_date')
                    ->label(__('employees::filament/resources/employee/relation-manager/warnings.table.columns.effective-date'))
                    ->date()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('expiry_date')
                    ->label(__('employees::filament/resources/employee/relation-manager/warnings.table.columns.expiry-date'))
                    ->date()
                    ->sortable()
                    ->color(function ($record) {
                        if ($record->isExpired()) {
                            return 'danger';
                        }

                        return null;
                    })
                    ->placeholder('—'),
                IconColumn::make('is_acknowledged')
                    ->label(__('employees::filament/resources/employee/relation-manager/warnings.table.columns.is-acknowledged'))
                    ->boolean(),
                TextColumn::make('acknowledgedByUser.name')
                    ->label(__('employees::filament/resources/employee/relation-manager/warnings.table.columns.acknowledged-by'))
                    ->placeholder('—')
                    ->visible(fn () => false),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('employees::filament/resources/employee/relation-manager/warnings.table.header-actions.add-warning'))
                    ->icon('heroicon-o-exclamation-triangle')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('employees::filament/resources/employee/relation-manager/warnings.table.actions.create.notification.title'))
                            ->body(__('employees::filament/resources/employee/relation-manager/warnings.table.actions.create.notification.body'))
                    )
                    ->after(function ($record) {
                        if ($record->employee?->work_email) {
                            Mail::to($record->employee->work_email)->send(new EmployeeWarningMail($record));
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('employees::filament/resources/employee/relation-manager/warnings.table.actions.edit.notification.title'))
                            ->body(__('employees::filament/resources/employee/relation-manager/warnings.table.actions.edit.notification.body'))
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('employees::filament/resources/employee/relation-manager/warnings.table.actions.delete.notification.title'))
                            ->body(__('employees::filament/resources/employee/relation-manager/warnings.table.actions.delete.notification.body'))
                    ),
                Action::make('send')
                    ->label(__('employees::filament/resources/employee/relation-manager/warnings.table.actions.send'))
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->visible(fn ($record) => filled($record->employee?->work_email))
                    ->action(function ($record) {
                        Mail::to($record->employee->work_email)->send(new EmployeeWarningMail($record));

                        Notification::make()
                            ->success()
                            ->title(__('employees::filament/resources/employee/relation-manager/warnings.table.actions.send.notification.title'))
                            ->body(__('employees::filament/resources/employee/relation-manager/warnings.table.actions.send.notification.body'))
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('employees::filament/resources/employee/relation-manager/warnings.table.bulk-actions.delete.notification.title'))
                                ->body(__('employees::filament/resources/employee/relation-manager/warnings.table.bulk-actions.delete.notification.body'))
                        ),
                ]),
            ]);
    }
}
