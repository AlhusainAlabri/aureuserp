<?php

namespace Webkul\Correspondence\Filament\Resources;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Illuminate\Support\Str;
use Webkul\Correspondence\Filament\Resources\DepartmentResource\Pages\CreateDepartment;
use Webkul\Correspondence\Filament\Resources\DepartmentResource\Pages\EditDepartment;
use Webkul\Correspondence\Filament\Resources\DepartmentResource\Pages\ListDepartments;
use Webkul\Correspondence\Models\Department;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 56;

    protected static ?string $slug = 'correspondence/departments';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('correspondence::correspondence.departments.navigation');
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.correspondence');
    }

    public static function getModelLabel(): string
    {
        return __('correspondence::correspondence.departments.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('correspondence::correspondence.departments.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('correspondence::correspondence.departments.section'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('correspondence::correspondence.departments.name'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, callable $set, callable $get): void {
                                if (filled($get('code'))) {
                                    return;
                                }

                                $set('code', strtoupper(Str::limit(Str::slug($state ?? '', ''), 4, '')));
                            }),
                        TextInput::make('code')
                            ->label(__('correspondence::correspondence.departments.code'))
                            ->required()
                            ->maxLength(4)
                            ->unique(ignoreRecord: true),
                        Select::make('manager_id')
                            ->label(__('correspondence::correspondence.departments.manager'))
                            ->options(fn () => User::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                        Select::make('company_id')
                            ->label(__('correspondence::correspondence.departments.company'))
                            ->options(fn () => Company::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => Company::query()->value('id')),
                        Select::make('employees_department_id')
                            ->label(__('correspondence::correspondence.departments.employees_department'))
                            ->options(fn () => DatabaseSchema::hasTable('employees_departments')
                                ? \Webkul\Employee\Models\Department::query()->pluck('name', 'id')
                                : [])
                            ->searchable()
                            ->preload()
                            ->visible(fn (): bool => DatabaseSchema::hasTable('employees_departments')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('correspondence::correspondence.departments.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label(__('correspondence::correspondence.departments.code'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('manager.name')
                    ->label(__('correspondence::correspondence.departments.manager'))
                    ->placeholder('-'),
                TextColumn::make('employeesDepartment.name')
                    ->label(__('correspondence::correspondence.departments.employees_department'))
                    ->placeholder('-')
                    ->visible(fn (): bool => DatabaseSchema::hasTable('employees_departments')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListDepartments::route('/'),
            'create' => CreateDepartment::route('/create'),
            'edit'   => EditDepartment::route('/{record}/edit'),
        ];
    }
}
