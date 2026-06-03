<?php

namespace Webkul\Payroll\Filament\Resources;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Webkul\Payroll\Enums\CalculationType;
use Webkul\Payroll\Filament\Concerns\AppendsLocaleToResourceUrls;
use Webkul\Payroll\Filament\Resources\EmployeeComponentResource\Pages\CreateEmployeeComponent;
use Webkul\Payroll\Filament\Resources\EmployeeComponentResource\Pages\EditEmployeeComponent;
use Webkul\Payroll\Filament\Resources\EmployeeComponentResource\Pages\ListEmployeeComponents;
use Webkul\Payroll\Models\EmployeeComponent;
use Webkul\Payroll\Models\SalaryComponent;

class EmployeeComponentResource extends Resource
{
    use AppendsLocaleToResourceUrls;

    protected static ?string $model = EmployeeComponent::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $slug = 'payroll/employee-components';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return static::getPluralModelLabel();
    }

    public static function getNavigationGroup(): string
    {
        return __('payroll::payroll.navigation.group');
    }

    public static function getModelLabel(): string
    {
        return __('payroll::payroll.models.employee_component');
    }

    public static function getPluralModelLabel(): string
    {
        return __('payroll::payroll.models_plural.employee_component');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('payroll::payroll.form.sections.assignment'))
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('employee_id')
                            ->label(__('payroll::payroll.fields.employee'))
                            ->relationship('employee', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(1),
                        Select::make('component_id')
                            ->label(__('payroll::payroll.fields.component'))
                            ->relationship(
                                'component',
                                'name',
                                modifyQueryUsing: fn ($query) => $query->orderBy('sort_order'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (SalaryComponent $record): string => $record->display_name)
                            ->searchable(['name', 'name_ar'])
                            ->preload()
                            ->required()
                            ->live()
                            ->columnSpan(1),
                        TextInput::make('amount')
                            ->label(__('payroll::payroll.fields.amount'))
                            ->numeric()
                            ->visible(fn (Get $get): bool => static::componentUsesAmount((int) $get('component_id')))
                            ->columnSpan(1),
                        TextInput::make('percent')
                            ->label(__('payroll::payroll.fields.percent'))
                            ->numeric()
                            ->suffix('%')
                            ->visible(fn (Get $get): bool => static::componentUsesPercent((int) $get('component_id')))
                            ->columnSpan(1),
                        DatePicker::make('start_date')
                            ->label(__('payroll::payroll.fields.start_date'))
                            ->required()
                            ->native(false)
                            ->columnSpan(1),
                        DatePicker::make('end_date')
                            ->label(__('payroll::payroll.fields.end_date'))
                            ->native(false)
                            ->columnSpan(1),
                        Textarea::make('notes')
                            ->label(__('payroll::payroll.fields.notes'))
                            ->columnSpanFull(),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label(__('payroll::payroll.fields.employee'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('component.code')
                    ->label(__('payroll::payroll.fields.code'))
                    ->sortable(),
                TextColumn::make('component.name')
                    ->label(__('payroll::payroll.fields.component'))
                    ->formatStateUsing(fn (?string $state, EmployeeComponent $record): string => $record->component?->display_name ?? (string) $state)
                    ->searchable(['component.name', 'component.name_ar']),
                TextColumn::make('amount')
                    ->label(__('payroll::payroll.fields.amount'))
                    ->formatStateUsing(fn (?string $state): string => $state !== null ? SalaryComponentResource::formatMoney((float) $state) : '-'),
                TextColumn::make('percent')
                    ->label(__('payroll::payroll.fields.percent'))
                    ->suffix('%')
                    ->placeholder('-'),
                TextColumn::make('start_date')
                    ->label(__('payroll::payroll.fields.start_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label(__('payroll::payroll.fields.end_date'))
                    ->date()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('employee_id')
                    ->label(__('payroll::payroll.filters.employee'))
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('component_id')
                    ->label(__('payroll::payroll.fields.component'))
                    ->relationship(
                        'component',
                        'name',
                        modifyQueryUsing: fn ($query) => $query->orderBy('sort_order'),
                    )
                    ->getOptionLabelFromRecordUsing(fn (SalaryComponent $record): string => $record->display_name)
                    ->searchable(['name', 'name_ar'])
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListEmployeeComponents::route('/'),
            'create' => CreateEmployeeComponent::route('/create'),
            'edit'   => EditEmployeeComponent::route('/{record}/edit'),
        ];
    }

    protected static function componentUsesAmount(?int $componentId): bool
    {
        if (! $componentId) {
            return true;
        }

        $component = SalaryComponent::query()->find($componentId);

        return $component && in_array($component->calculation_type, [CalculationType::Fixed, CalculationType::HoursBased], true);
    }

    protected static function componentUsesPercent(?int $componentId): bool
    {
        if (! $componentId) {
            return false;
        }

        $component = SalaryComponent::query()->find($componentId);

        return $component && in_array($component->calculation_type, [CalculationType::PercentOfBasic, CalculationType::PercentOfGross], true);
    }
}
