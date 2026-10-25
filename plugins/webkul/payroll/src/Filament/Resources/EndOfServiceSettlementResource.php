<?php

namespace Webkul\Payroll\Filament\Resources;

use App\Filament\Infolists\ApprovalStatusSection;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Webkul\Payroll\Enums\SettlementStatus;
use Webkul\Payroll\Filament\Concerns\AppendsLocaleToResourceUrls;
use Webkul\Payroll\Filament\Resources\EndOfServiceSettlementResource\Pages\EditEndOfServiceSettlement;
use Webkul\Payroll\Filament\Resources\EndOfServiceSettlementResource\Pages\ListEndOfServiceSettlements;
use Webkul\Payroll\Filament\Resources\EndOfServiceSettlementResource\Pages\ViewEndOfServiceSettlement;
use Webkul\Payroll\Models\EndOfServiceSettlement;
use Wezlo\FilamentApproval\Columns\ApprovalStatusColumn;
use Wezlo\FilamentApproval\RelationManagers\ApprovalsRelationManager;

class EndOfServiceSettlementResource extends Resource
{
    use AppendsLocaleToResourceUrls;

    protected static ?string $model = EndOfServiceSettlement::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-right-start-on-rectangle';

    protected static ?string $slug = 'payroll/end-of-service-settlements';

    protected static ?int $navigationSort = 35;

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
        return __('payroll::payroll.models.end_of_service_settlement');
    }

    public static function getPluralModelLabel(): string
    {
        return __('payroll::payroll.models_plural.end_of_service_settlement');
    }

    /**
     * Settlements are created from the departed employee's page, never from a blank form.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('payroll::payroll.settlement.sections.adjustments'))
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('unused_leave_days')
                            ->label(__('payroll::payroll.settlement.fields.unused_leave_days'))
                            ->helperText(__('payroll::payroll.settlement.unused_leave_days_hint'))
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->columnSpan(1),
                        TextInput::make('final_salary_amount')
                            ->label(__('payroll::payroll.settlement.fields.final_salary_amount'))
                            ->helperText(__('payroll::payroll.settlement.final_salary_hint'))
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->columnSpan(1),
                        TextInput::make('other_adjustment')
                            ->label(__('payroll::payroll.settlement.fields.other_adjustment'))
                            ->helperText(__('payroll::payroll.settlement.other_adjustment_hint'))
                            ->numeric()
                            ->required()
                            ->columnSpan(1),
                        Textarea::make('adjustment_note')
                            ->label(__('payroll::payroll.settlement.fields.adjustment_note'))
                            ->rows(3)
                            ->maxLength(2000)
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
                TextColumn::make('departure_date')
                    ->label(__('payroll::payroll.settlement.fields.departure_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('gratuity_amount')
                    ->label(__('payroll::payroll.settlement.fields.gratuity_amount'))
                    ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) $state))
                    ->sortable(),
                TextColumn::make('net_amount')
                    ->label(__('payroll::payroll.settlement.fields.net_amount'))
                    ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) $state))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('payroll::payroll.settlement.fields.status'))
                    ->badge(),
                ApprovalStatusColumn::make()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('payroll::payroll.settlement.fields.status'))
                    ->options(SettlementStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('departure_date', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        $money = fn (?string $state): string => SalaryComponentResource::formatMoney((float) $state);

        return $schema->components([
            Section::make(__('payroll::payroll.settlement.sections.service'))
                ->schema([
                    TextEntry::make('employee.name')->label(__('payroll::payroll.fields.employee')),
                    TextEntry::make('status')->label(__('payroll::payroll.settlement.fields.status'))->badge(),
                    TextEntry::make('join_date')->label(__('payroll::payroll.settlement.fields.join_date'))->date(),
                    TextEntry::make('departure_date')->label(__('payroll::payroll.settlement.fields.departure_date'))->date(),
                    TextEntry::make('service_days')->label(__('payroll::payroll.settlement.fields.service_days')),
                    TextEntry::make('last_basic')->label(__('payroll::payroll.settlement.fields.last_basic'))->formatStateUsing($money),
                ])
                ->columns(2),
            Section::make(__('payroll::payroll.settlement.sections.gratuity'))
                ->schema([
                    TextEntry::make('gratuity_breakdown')
                        ->label(__('payroll::payroll.settlement.fields.gratuity_breakdown'))
                        ->state(fn (EndOfServiceSettlement $record): array => static::breakdownLines($record))
                        ->listWithLineBreaks()
                        ->columnSpanFull(),
                ]),
            Section::make(__('payroll::payroll.settlement.sections.amounts'))
                ->schema([
                    TextEntry::make('gratuity_amount')->label(__('payroll::payroll.settlement.fields.gratuity_amount'))->formatStateUsing($money),
                    TextEntry::make('unused_leave_days')->label(__('payroll::payroll.settlement.fields.unused_leave_days')),
                    TextEntry::make('leave_payout_amount')->label(__('payroll::payroll.settlement.fields.leave_payout_amount'))->formatStateUsing($money),
                    TextEntry::make('final_salary_amount')->label(__('payroll::payroll.settlement.fields.final_salary_amount'))->formatStateUsing($money),
                    TextEntry::make('other_adjustment')->label(__('payroll::payroll.settlement.fields.other_adjustment'))->formatStateUsing($money),
                    TextEntry::make('loan_offset_amount')->label(__('payroll::payroll.settlement.fields.loan_offset_amount'))->formatStateUsing($money),
                    TextEntry::make('net_amount')->label(__('payroll::payroll.settlement.fields.net_amount'))->formatStateUsing($money)->weight('bold'),
                    TextEntry::make('adjustment_note')->label(__('payroll::payroll.settlement.fields.adjustment_note'))->placeholder('-')->columnSpanFull(),
                ])
                ->columns(2),
            ApprovalStatusSection::make(),
        ]);
    }

    /**
     * @return list<string>
     */
    public static function breakdownLines(EndOfServiceSettlement $settlement): array
    {
        $breakdown = $settlement->gratuity_breakdown ?? [];

        if (filled($breakdown['reason'] ?? null)) {
            return [__('payroll::payroll.gratuity.reasons.'.$breakdown['reason'])];
        }

        return collect($breakdown['rows'] ?? [])
            ->map(fn (array $row): string => __('payroll::payroll.settlement.breakdown_row', [
                'from'   => rtrim(rtrim(number_format((float) $row['from'], 2, '.', ''), '0'), '.'),
                'to'     => $row['to'] === null ? __('payroll::payroll.gratuity.open_ended') : rtrim(rtrim(number_format((float) $row['to'], 2, '.', ''), '0'), '.'),
                'years'  => number_format((float) $row['years'], 4),
                'days'   => number_format((float) $row['days'], 2),
                'amount' => SalaryComponentResource::formatMoney((float) $row['amount']),
            ]))
            ->all();
    }

    public static function getRelationManagers(): array
    {
        return [
            ApprovalsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEndOfServiceSettlements::route('/'),
            'view'  => ViewEndOfServiceSettlement::route('/{record}'),
            'edit'  => EditEndOfServiceSettlement::route('/{record}/edit'),
        ];
    }
}
