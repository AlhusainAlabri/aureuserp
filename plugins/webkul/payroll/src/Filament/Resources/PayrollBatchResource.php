<?php

namespace Webkul\Payroll\Filament\Resources;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as DbSchema;
use Webkul\Account\Models\Journal;
use Webkul\Payroll\Enums\BatchStatus;
use Webkul\Payroll\Filament\Concerns\AppendsLocaleToResourceUrls;
use Webkul\Payroll\Filament\Resources\PayrollBatchResource\Pages\CreatePayrollBatch;
use Webkul\Payroll\Filament\Resources\PayrollBatchResource\Pages\EditPayrollBatch;
use Webkul\Payroll\Filament\Resources\PayrollBatchResource\Pages\ListPayrollBatches;
use Webkul\Payroll\Filament\Resources\PayrollBatchResource\Pages\ViewPayrollBatch;
use Webkul\Payroll\Filament\Resources\PayrollBatchResource\RelationManagers\PayslipsRelationManager;
use Webkul\Payroll\Models\PayrollBatch;
use Webkul\Payroll\Support\PayrollCalendar;
use Wezlo\FilamentApproval\Columns\ApprovalStatusColumn;
use Wezlo\FilamentApproval\Infolists\ApprovalStatusSection;
use Wezlo\FilamentApproval\RelationManagers\ApprovalsRelationManager;

class PayrollBatchResource extends Resource
{
    use AppendsLocaleToResourceUrls;

    protected static ?string $model = PayrollBatch::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $slug = 'payroll/payroll-batches';

    protected static ?int $navigationSort = 10;

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
        return __('payroll::payroll.models.payroll_batch');
    }

    public static function getPluralModelLabel(): string
    {
        return __('payroll::payroll.models_plural.payroll_batch');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('payroll::payroll.form.sections.period'))
                ->schema([
                    Grid::make(2)->schema([
                        Placeholder::make('reference_number')
                            ->label(__('payroll::payroll.fields.reference_number'))
                            ->content(fn (?PayrollBatch $record): string => $record?->reference_number ?? __('payroll::payroll.fields.auto_generated')),
                        Select::make('status')
                            ->label(__('payroll::payroll.fields.status'))
                            ->options(BatchStatus::class)
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit')
                            ->columnSpan(1),
                        TextInput::make('period_year')
                            ->label(__('payroll::payroll.fields.period_year'))
                            ->numeric()
                            ->required()
                            ->default(now()->year)
                            ->columnSpan(1),
                        Select::make('period_month')
                            ->label(__('payroll::payroll.fields.period_month'))
                            ->options(fn (): array => PayrollCalendar::monthOptions())
                            ->required()
                            ->default(now()->month)
                            ->native(false)
                            ->columnSpan(1),
                        DatePicker::make('pay_date')
                            ->label(__('payroll::payroll.fields.pay_date'))
                            ->required()
                            ->native(false)
                            ->locale(fn (): string => app()->getLocale())
                            ->displayFormat(fn (): string => app()->getLocale() === 'ar' ? 'j F Y' : 'M j, Y')
                            ->default(fn (): string => now()->copy()->day(min(25, now()->daysInMonth))->toDateString())
                            ->columnSpan(1),
                        Select::make('journal_id')
                            ->label(__('payroll::payroll.fields.journal'))
                            ->options(fn (): array => static::journalOptions())
                            ->searchable()
                            ->preload()
                            ->visible(fn (): bool => class_exists(Journal::class) && DbSchema::hasTable('accounts_journals'))
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
                TextColumn::make('reference_number')
                    ->label(__('payroll::payroll.fields.reference_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('period')
                    ->label(__('payroll::payroll.fields.period'))
                    ->state(fn (PayrollBatch $record): string => PayrollCalendar::formatPeriod(
                        (int) $record->period_month,
                        (int) $record->period_year,
                    )),
                TextColumn::make('pay_date')
                    ->label(__('payroll::payroll.fields.pay_date'))
                    ->formatStateUsing(fn (PayrollBatch $record): string => PayrollCalendar::formatDate($record->pay_date))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('payroll::payroll.fields.status'))
                    ->badge(),
                TextColumn::make('employee_count')
                    ->label(__('payroll::payroll.fields.employee_count'))
                    ->sortable(),
                TextColumn::make('total_gross')
                    ->label(__('payroll::payroll.fields.total_gross'))
                    ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0)))
                    ->sortable(),
                TextColumn::make('total_net')
                    ->label(__('payroll::payroll.fields.total_net'))
                    ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0)))
                    ->sortable(),
                ApprovalStatusColumn::make(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('payroll::payroll.filters.status'))
                    ->options(BatchStatus::class),
                SelectFilter::make('period_year')
                    ->label(__('payroll::payroll.filters.year'))
                    ->options(fn (): array => collect(range(now()->year - 3, now()->year + 1))
                        ->mapWithKeys(fn (int $year): array => [$year => (string) $year])
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (PayrollBatch $record): bool => $record->isDraft()),
                DeleteAction::make(),
            ])
            ->defaultSort('period_year', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('payroll::payroll.form.sections.totals'))
                ->schema([
                    TextEntry::make('reference_number')->label(__('payroll::payroll.fields.reference_number')),
                    TextEntry::make('period')
                        ->label(__('payroll::payroll.fields.period'))
                        ->state(fn (PayrollBatch $record): string => PayrollCalendar::formatPeriod(
                            (int) $record->period_month,
                            (int) $record->period_year,
                        )),
                    TextEntry::make('pay_date')
                        ->label(__('payroll::payroll.fields.pay_date'))
                        ->formatStateUsing(fn (PayrollBatch $record): string => PayrollCalendar::formatDate($record->pay_date)),
                    TextEntry::make('status')->label(__('payroll::payroll.fields.status'))->badge(),
                    TextEntry::make('employee_count')->label(__('payroll::payroll.fields.employee_count')),
                    TextEntry::make('total_gross')
                        ->label(__('payroll::payroll.fields.total_gross'))
                        ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
                    TextEntry::make('total_deductions')
                        ->label(__('payroll::payroll.fields.total_deductions'))
                        ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
                    TextEntry::make('total_net')
                        ->label(__('payroll::payroll.fields.total_net'))
                        ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
                    TextEntry::make('total_employer_cost')
                        ->label(__('payroll::payroll.fields.total_employer_cost'))
                        ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
                    TextEntry::make('notes')->label(__('payroll::payroll.fields.notes'))->placeholder('-')->columnSpanFull(),
                ])
                ->columns(2),
            ApprovalStatusSection::make(),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationGroup::make(__('payroll::payroll.relations.approvals'), [
                ApprovalsRelationManager::class,
            ]),
            RelationGroup::make(__('payroll::payroll.relations.payslips'), [
                PayslipsRelationManager::class,
            ]),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPayrollBatches::route('/'),
            'create' => CreatePayrollBatch::route('/create'),
            'view'   => ViewPayrollBatch::route('/{record}'),
            'edit'   => EditPayrollBatch::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<int|string, string>
     */
    protected static function journalOptions(): array
    {
        if (! class_exists(Journal::class) || ! DbSchema::hasTable('accounts_journals')) {
            return [];
        }

        return Journal::query()->pluck('name', 'id')->all();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderByDesc('period_year')->orderByDesc('period_month');
    }
}
