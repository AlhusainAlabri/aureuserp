<?php

namespace App\Filament\Concerns;

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
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

trait ExtendedEmployeeDocumentsRelation
{
    /** @return array<string, string> */
    protected static function documentTypeOptions(): array
    {
        return [
            'id_card'              => __('employees::filament/resources/employee.relation-manager/documents.form.fields.id-card'),
            'passport'             => __('employees::filament/resources/employee.relation-manager/documents.form.fields.passport'),
            'residence_permit'     => __('employees::filament/resources/employee.relation-manager/documents.form.fields.residence-permit'),
            'contract'             => __('employees::filament/resources/employee.relation-manager/documents.form.fields.contract'),
            'certificate'          => __('employees::filament/resources/employee.relation-manager/documents.form.fields.certificate'),
            'professional_conduct' => __('hr-extensions::employee.document_types.professional_conduct'),
            'other'                => __('employees::filament/resources/employee.relation-manager/documents.form.fields.other'),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    Hidden::make('creator_id')
                        ->default(fn () => Auth::id()),
                    Select::make('document_type')
                        ->label(__('employees::filament/resources/employee.relation-manager/documents.form.fields.document-type'))
                        ->options(static::documentTypeOptions())
                        ->required(),
                    TextInput::make('document_name')
                        ->label(__('employees::filament/resources/employee.relation-manager/documents.form.fields.document-name'))
                        ->required()
                        ->maxLength(255),
                    FileUpload::make('file_path')
                        ->label(__('employees::filament/resources/employee.relation-manager/documents.form.fields.file'))
                        ->directory(fn ($livewire) => 'employees/'.$livewire->getOwnerRecord()->id.'/documents')
                        ->visibility('private')
                        ->preserveFilenames()
                        ->live()
                        ->afterStateUpdated(function (Set $set, $state) {
                            if (empty($state)) {
                                return;
                            }

                            $fileName = is_array($state) ? basename($state[0] ?? '') : basename($state);

                            if ($fileName) {
                                $set('document_name', $fileName);
                            }
                        })
                        ->required(),
                    DatePicker::make('expiry_date')
                        ->label(__('employees::filament/resources/employee.relation-manager/documents.form.fields.expiry-date'))
                        ->native(false)
                        ->suffixIcon('heroicon-o-calendar')
                        ->nullable(),
                    TextInput::make('notes')
                        ->label(__('employees::filament/resources/employee.relation-manager/documents.form.fields.notes'))
                        ->nullable()
                        ->maxLength(500),
                ])->columns(2)->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        $options = static::documentTypeOptions();

        return $table
            ->columns([
                TextColumn::make('document_type')
                    ->label(__('employees::filament/resources/employee.relation-manager/documents.table.columns.document-type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'id_card'              => 'info',
                        'passport'             => 'purple',
                        'contract'             => 'teal',
                        'certificate'          => 'success',
                        'residence_permit'     => 'warning',
                        'professional_conduct' => 'primary',
                        default                => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => $options[$state] ?? $options['other']),
                TextColumn::make('document_name')
                    ->label(__('employees::filament/resources/employee.relation-manager/documents.table.columns.document-name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('expiry_date')
                    ->label(__('employees::filament/resources/employee.relation-manager/documents.table.columns.expiry-date'))
                    ->date()
                    ->color(function ($record) {
                        if ($record->isExpired()) {
                            return 'danger';
                        }

                        if ($record->isExpiringSoon()) {
                            return 'warning';
                        }

                        return null;
                    }),
                TextColumn::make('notes')
                    ->label(__('employees::filament/resources/employee.relation-manager/documents.table.columns.notes'))
                    ->limit(30),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label(__('employees::filament/resources/employee.relation-manager/documents.table.actions.view'))
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalContent(fn ($record) => view('employees::filament.modals.document-preview', ['record' => $record]))
                    ->modalHeading(fn ($record) => $record->document_name)
                    ->modalWidth(Width::SevenExtraLarge)
                    ->extraModalWindowAttributes(['style' => 'height: 92vh; max-height: 92vh; display: flex; flex-direction: column;'])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('employees::filament/resources/employee.relation-manager/documents.preview.close'))
                    ->visible(fn ($record) => $record->file_path && Storage::disk('local')->exists($record->file_path)),
                Action::make('download')
                    ->label(__('employees::filament/resources/employee.relation-manager/documents.table.actions.download'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function ($record) {
                        return redirect()->route('employees.documents.download', ['document' => $record]);
                    })
                    ->visible(fn ($record) => $record->file_path && Storage::disk('local')->exists($record->file_path)),
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
