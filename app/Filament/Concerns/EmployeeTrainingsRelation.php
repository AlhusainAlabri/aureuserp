<?php

namespace App\Filament\Concerns;

use App\Enums\Hr\TrainingStatus;
use App\Enums\Hr\TrainingType;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

trait EmployeeTrainingsRelation
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Hidden::make('creator_id')
                            ->default(fn () => Auth::id()),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('course_name')
                                    ->label(__('hr-extensions::training.fields.course_name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                TextInput::make('provider')
                                    ->label(__('hr-extensions::training.fields.provider'))
                                    ->maxLength(255),
                                Select::make('type')
                                    ->label(__('hr-extensions::training.fields.type'))
                                    ->options(TrainingType::class)
                                    ->default(TrainingType::External)
                                    ->required()
                                    ->native(false),
                                Select::make('status')
                                    ->label(__('hr-extensions::training.fields.status'))
                                    ->options(TrainingStatus::class)
                                    ->default(TrainingStatus::Planned)
                                    ->required()
                                    ->native(false),
                                DatePicker::make('start_date')
                                    ->label(__('hr-extensions::training.fields.start_date'))
                                    ->required()
                                    ->native(false)
                                    ->suffixIcon('heroicon-o-calendar'),
                                DatePicker::make('end_date')
                                    ->label(__('hr-extensions::training.fields.end_date'))
                                    ->native(false)
                                    ->suffixIcon('heroicon-o-calendar'),
                                TextInput::make('duration_hours')
                                    ->label(__('hr-extensions::training.fields.duration_hours'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01),
                                TextInput::make('cost')
                                    ->label(__('hr-extensions::training.fields.cost'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.001)
                                    ->prefix('OMR'),
                            ]),
                        FileUpload::make('certificate_path')
                            ->label(__('hr-extensions::training.fields.certificate'))
                            ->directory(fn ($livewire) => 'employees/'.$livewire->getOwnerRecord()->id.'/trainings/certificates')
                            ->visibility('private')
                            ->preserveFilenames()
                            ->columnSpanFull(),
                        DatePicker::make('certificate_expiry_date')
                            ->label(__('hr-extensions::training.fields.certificate_expiry_date'))
                            ->native(false)
                            ->suffixIcon('heroicon-o-calendar'),
                        Textarea::make('notes')
                            ->label(__('hr-extensions::training.fields.notes'))
                            ->maxLength(2000)
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('course_name')
                    ->label(__('hr-extensions::training.fields.course_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('provider')
                    ->label(__('hr-extensions::training.fields.provider'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('type')
                    ->label(__('hr-extensions::training.fields.type'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('hr-extensions::training.fields.status'))
                    ->badge(),
                TextColumn::make('start_date')
                    ->label(__('hr-extensions::training.fields.start_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label(__('hr-extensions::training.fields.end_date'))
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cost')
                    ->label(__('hr-extensions::training.fields.cost'))
                    ->formatStateUsing(fn (?string $state): string => $state !== null
                        ? 'OMR '.number_format((float) $state, 3)
                        : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('certificate_expiry_date')
                    ->label(__('hr-extensions::training.fields.certificate_expiry_date'))
                    ->date()
                    ->color(function ($record): ?string {
                        if ($record->certificate_expiry_date?->isPast()) {
                            return 'danger';
                        }

                        if ($record->hasExpiringCertificate()) {
                            return 'warning';
                        }

                        return null;
                    }),
            ])
            ->defaultSort('start_date', 'desc')
            ->emptyStateHeading(__('hr-extensions::training.empty_heading'))
            ->emptyStateDescription(__('hr-extensions::training.empty_description'))
            ->headerActions([
                CreateAction::make()
                    ->label(__('hr-extensions::training.actions.add')),
            ])
            ->recordActions([
                Action::make('viewCertificate')
                    ->label(__('hr-extensions::training.actions.view_certificate'))
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record): ?string => $record->certificateTemporaryUrl())
                    ->openUrlInNewTab()
                    ->visible(fn ($record): bool => filled($record->certificate_path)
                        && Storage::disk('private')->exists($record->certificate_path)),
                Action::make('downloadCertificate')
                    ->label(__('hr-extensions::training.actions.download_certificate'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn ($record) => Storage::disk('private')->download($record->certificate_path))
                    ->visible(fn ($record): bool => filled($record->certificate_path)
                        && Storage::disk('private')->exists($record->certificate_path)),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
