<?php

namespace Webkul\Payroll\Filament\Resources;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Payroll\Enums\PayslipStatus;
use Webkul\Payroll\Filament\Concerns\AppendsLocaleToResourceUrls;
use Webkul\Payroll\Filament\Resources\PayslipResource\Pages\ListPayslips;
use Webkul\Payroll\Filament\Resources\PayslipResource\Pages\ViewPayslip;
use Webkul\Payroll\Models\Payslip;

class PayslipResource extends Resource
{
    use AppendsLocaleToResourceUrls;

    protected static ?string $model = Payslip::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $slug = 'payroll/payslips';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'reference_number';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

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
        return __('payroll::payroll.models.payslip');
    }

    public static function getPluralModelLabel(): string
    {
        return __('payroll::payroll.models_plural.payslip');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')
                    ->label(__('payroll::payroll.fields.reference_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.name')
                    ->label(__('payroll::payroll.fields.employee'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('period')
                    ->label(__('payroll::payroll.fields.period'))
                    ->state(fn (Payslip $record): string => sprintf('%02d/%d', $record->period_month, $record->period_year)),
                TextColumn::make('basic_salary')
                    ->label(__('payroll::payroll.fields.basic_salary'))
                    ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
                TextColumn::make('gross_amount')
                    ->label(__('payroll::payroll.fields.gross_amount'))
                    ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
                TextColumn::make('deductions_amount')
                    ->label(__('payroll::payroll.fields.deductions_amount'))
                    ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
                TextColumn::make('net_amount')
                    ->label(__('payroll::payroll.fields.net_amount'))
                    ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0)))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('payroll::payroll.fields.status'))
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('payroll::payroll.filters.status'))
                    ->options(PayslipStatus::class),
                SelectFilter::make('employee_id')
                    ->label(__('payroll::payroll.filters.employee'))
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('batch_id')
                    ->label(__('payroll::payroll.fields.batch'))
                    ->relationship('batch', 'reference_number')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make()
                    ->visible(fn (Payslip $record): bool => $record->isDraft()),
            ])
            ->defaultSort('reference_number', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('payroll::payroll.form.sections.details'))
                ->schema([
                    TextEntry::make('reference_number')->label(__('payroll::payroll.fields.reference_number')),
                    TextEntry::make('employee.name')->label(__('payroll::payroll.fields.employee')),
                    TextEntry::make('batch.reference_number')->label(__('payroll::payroll.fields.batch')),
                    TextEntry::make('period')
                        ->label(__('payroll::payroll.fields.period'))
                        ->state(fn (Payslip $record): string => sprintf('%02d/%d', $record->period_month, $record->period_year)),
                    TextEntry::make('status')->label(__('payroll::payroll.fields.status'))->badge(),
                    TextEntry::make('working_days')->label(__('payroll::payroll.fields.working_days')),
                    TextEntry::make('worked_days')->label(__('payroll::payroll.fields.worked_days')),
                    TextEntry::make('unpaid_leave_days')->label(__('payroll::payroll.fields.unpaid_leave_days')),
                    TextEntry::make('payment_method')->label(__('payroll::payroll.fields.payment_method'))->badge(),
                    TextEntry::make('bank_name')->label(__('payroll::payroll.fields.bank_name'))->placeholder('-'),
                    TextEntry::make('bank_account_number')->label(__('payroll::payroll.fields.bank_account_number'))->placeholder('-'),
                ])
                ->columns(2),
            Section::make(__('payroll::payroll.form.sections.totals'))
                ->schema([
                    TextEntry::make('basic_salary')
                        ->label(__('payroll::payroll.fields.basic_salary'))
                        ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
                    TextEntry::make('gross_amount')
                        ->label(__('payroll::payroll.fields.gross_amount'))
                        ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
                    TextEntry::make('deductions_amount')
                        ->label(__('payroll::payroll.fields.deductions_amount'))
                        ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
                    TextEntry::make('net_amount')
                        ->label(__('payroll::payroll.fields.net_amount'))
                        ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
                    TextEntry::make('employer_cost')
                        ->label(__('payroll::payroll.fields.employer_cost'))
                        ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
                ])
                ->columns(2),
            Section::make(__('payroll::payroll.form.sections.lines'))
                ->schema([
                    RepeatableEntry::make('lines')
                        ->label('')
                        ->schema([
                            TextEntry::make('code')->label(__('payroll::payroll.fields.code')),
                            TextEntry::make('name')->label(__('payroll::payroll.fields.name')),
                            TextEntry::make('amount')
                                ->label(__('payroll::payroll.fields.amount'))
                                ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
                        ])
                        ->columns(3),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayslips::route('/'),
            'view'  => ViewPayslip::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['employee', 'batch', 'lines']);
    }
}
