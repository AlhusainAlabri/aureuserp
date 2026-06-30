<?php

namespace App\Filament\Extensions;

use App\Enums\Purchases\RequestType;
use App\Enums\Purchases\Urgency;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Webkul\Employee\Models\Department;
use Webkul\Employee\Models\Employee;
use Webkul\Field\Filament\Forms\Components\ProgressStepper as FormProgressStepper;
use Webkul\Partner\Models\Partner;
use Webkul\Product\Models\Product;
use Webkul\Support\Models\Currency;
use Webkul\TableViews\Filament\Components\PresetView;

class PurchaseOrderResourceExtensions
{
    public static function requiresVendor(mixed $requestType): bool
    {
        $type = self::resolveRequestType($requestType);

        return $type === null || $type === RequestType::StandardPurchase;
    }

    public static function isInternalRequest(mixed $requestType): bool
    {
        $type = self::resolveRequestType($requestType);

        return $type !== null && $type !== RequestType::StandardPurchase;
    }

    /** @return array<int, mixed> */
    public static function requestDetailsFormSection(): array
    {
        if (! DatabaseSchema::hasColumn('purchases_orders', 'request_type')) {
            return [];
        }

        return [
            Section::make(__('purchases-extensions::request.section_title'))
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('request_type')
                                ->label(__('purchases-extensions::request.fields.request_type'))
                                ->options(fn (): array => self::requestTypeOptions())
                                ->default(fn (): RequestType => RequestType::tryFrom((string) request()->query('request_type'))
                                    ?? RequestType::StandardPurchase)
                                ->required()
                                ->native(false)
                                ->live()
                                ->columnSpan(1),
                            Select::make('urgency')
                                ->label(__('purchases-extensions::request.fields.urgency'))
                                ->options(Urgency::class)
                                ->default(Urgency::Normal)
                                ->required()
                                ->native(false)
                                ->columnSpan(1),
                        ]),
                ])
                ->columns(1)
                ->columnSpanFull(),
            ...self::internalRequestFormSection(),
        ];
    }

    /** @return array<int, mixed> */
    public static function internalRequestFormSection(): array
    {
        if (! DatabaseSchema::hasColumn('purchases_orders', 'request_type')) {
            return [];
        }

        return [
            Section::make(__('purchases-extensions::request.internal_section'))
                ->schema([
                    Hidden::make('description')
                        ->default('')
                        ->dehydrated(true)
                        ->afterStateHydrated(function (Hidden $component, mixed $state): void {
                            if (! is_string($state)) {
                                $component->state('');
                            }
                        }),
                    Textarea::make('internal_justification')
                        ->label(__('purchases-extensions::request.fields.justification'))
                        ->required(fn (Get $get): bool => self::isInternalRequest($get('request_type')))
                        ->dehydrated(false)
                        ->live(onBlur: true)
                        ->afterStateHydrated(function (Textarea $component, mixed $state, Get $get): void {
                            $description = $get('description');

                            if (is_string($description) && $description !== '') {
                                $component->state($description);

                                return;
                            }

                            if (! is_string($state)) {
                                $component->state('');
                            }
                        })
                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                            $set('description', $state ?? '');
                        })
                        ->rows(4)
                        ->maxLength(5000)
                        ->columnSpanFull(),
                    TextInput::make('origin')
                        ->label(__('purchases-extensions::request.fields.item_description'))
                        ->maxLength(255)
                        ->columnSpanFull(),
                    DateTimePicker::make('planned_at')
                        ->label(__('purchases-extensions::request.fields.expected_delivery'))
                        ->native(false)
                        ->suffixIcon('heroicon-o-calendar'),
                    FileUpload::make('quotation_path')
                        ->label(__('purchases-extensions::request.fields.quotation'))
                        ->disk('private')
                        ->directory(fn (): string => 'purchases/quotations/'.now()->year)
                        ->columnSpanFull(),
                ])
                ->columns(1)
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => self::isInternalRequest($get('request_type'))),
        ];
    }

    /** @return array<string, string> */
    public static function requestTypeOptions(): array
    {
        $requestedType = request()->query('request_type');

        if (is_string($requestedType) && in_array($requestedType, RequestType::internalRequestTypes(), true)) {
            return collect(RequestType::cases())
                ->filter(fn (RequestType $type): bool => in_array($type->value, RequestType::internalRequestTypes(), true))
                ->mapWithKeys(fn (RequestType $type): array => [$type->value => $type->getLabel()])
                ->all();
        }

        return collect(RequestType::cases())
            ->mapWithKeys(fn (RequestType $type): array => [$type->value => $type->getLabel()])
            ->all();
    }

    /** @return array<int, TextColumn> */
    public static function extraTableColumns(): array
    {
        if (! DatabaseSchema::hasColumn('purchases_orders', 'request_type')) {
            return [];
        }

        return [
            TextColumn::make('request_type')
                ->label(__('purchases-extensions::request.fields.request_type'))
                ->badge()
                ->sortable()
                ->toggleable(),
            TextColumn::make('urgency')
                ->label(__('purchases-extensions::request.fields.urgency'))
                ->badge()
                ->sortable()
                ->toggleable(),
        ];
    }

    public static function defaultOmrCurrencyId(): ?int
    {
        return once(function (): ?int {
            if (! DatabaseSchema::hasTable('support_currencies')) {
                return null;
            }

            return Currency::query()
                ->where('name', 'OMR')
                ->orWhere('full_name', 'like', '%Omani%')
                ->value('id');
        });
    }

    /** @return array<int, string> */
    public static function localizedDepartmentOptions(): array
    {
        if (! class_exists(Department::class)) {
            return [];
        }

        return Department::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public static function formatOmrAmount(mixed $amount): string
    {
        return __('purchases-extensions::request.currency.format', [
            'amount' => number_format((float) ($amount ?? 0), 3),
        ]);
    }

    public static function applyTableCustomizations(Table $table): Table
    {
        $table = $table->columns(
            collect($table->getColumns())
                ->map(function ($column) {
                    if (! $column instanceof TextColumn) {
                        return $column;
                    }

                    $name = $column->getName();

                    if (! in_array($name, ['untaxed_amount', 'total_amount'], true)) {
                        return $column;
                    }

                    return TextColumn::make($name)
                        ->label($column->getLabel())
                        ->sortable()
                        ->toggleable()
                        ->formatStateUsing(fn (mixed $state): string => self::formatOmrAmount($state));
                })
                ->all()
        );

        return $table->filters(
            collect($table->getFilters())
                ->map(function ($filter) {
                    if ($filter->getName() !== 'requesting_department_id') {
                        return $filter;
                    }

                    return $filter->options(fn (): array => static::localizedDepartmentOptions());
                })
                ->all()
        );
    }

    /** @return array<string, PresetView> */
    public static function presetTableViews(): array
    {
        if (! DatabaseSchema::hasColumn('purchases_orders', 'request_type')) {
            return [];
        }

        $views = [
            'all' => PresetView::make(__('purchases-extensions::request.tabs.all'))
                ->icon('heroicon-o-queue-list')
                ->favorite(),
        ];

        foreach (RequestType::cases() as $type) {
            if ($type === RequestType::StandardPurchase) {
                continue;
            }

            $views[$type->value] = PresetView::make($type->getLabel())
                ->icon($type->getIcon())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('request_type', $type->value));
        }

        return $views;
    }

    public static function defaultRequestingDepartmentId(): ?int
    {
        if (! class_exists(Employee::class)) {
            return null;
        }

        $userId = Auth::id();

        if (! $userId) {
            return null;
        }

        $employee = Employee::query()->where('user_id', $userId)->first();

        if (! $employee) {
            return null;
        }

        return $employee->department_id;
    }

    /** @return array<int, Repeater> */
    public static function productRepeaterFields(Repeater $standardRepeater): array
    {
        if (! DatabaseSchema::hasColumn('purchases_orders', 'request_type')) {
            return [$standardRepeater];
        }

        return [
            self::configureStandardProductRepeater($standardRepeater),
            self::internalRequestLinesRepeater(),
        ];
    }

    public static function configureStandardProductRepeater(Repeater $repeater): Repeater
    {
        return $repeater->visible(fn (Get $get): bool => self::requiresVendor($get('request_type')));
    }

    public static function internalRequestLinesRepeater(): Repeater
    {
        return Repeater::make('internal_line_items')
            ->hiddenLabel()
            ->live()
            ->compact()
            ->label(__('purchases-extensions::request.lines.title'))
            ->addActionLabel(__('purchases-extensions::request.lines.add_line'))
            ->collapsible()
            ->defaultItems(1)
            ->dehydrated(true)
            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
            ->visible(fn (Get $get): bool => self::isInternalRequest($get('request_type')))
            ->schema([
                Hidden::make('product_id')
                    ->default(fn (): ?int => self::defaultInternalLineProductId())
                    ->dehydrated(true)
                    ->afterStateHydrated(function (Hidden $component, mixed $state): void {
                        if (blank($state)) {
                            $component->state(self::defaultInternalLineProductId());
                        }
                    }),
                Hidden::make('uom_id')
                    ->default(fn (): ?int => self::defaultInternalLineUomId())
                    ->dehydrated(true)
                    ->afterStateHydrated(function (Hidden $component, mixed $state): void {
                        if (blank($state)) {
                            $component->state(self::defaultInternalLineUomId());
                        }
                    }),
                TextInput::make('name')
                    ->label(__('purchases-extensions::request.lines.description'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('product_qty')
                    ->label(__('purchases-extensions::request.lines.quantity'))
                    ->required()
                    ->default(1)
                    ->numeric()
                    ->minValue(0.001)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get): void {
                        $set('product_uom_qty', $get('product_qty') ?? 1);
                        self::recalculateInternalLineTotals($set, $get);
                    }),
                TextInput::make('price_unit')
                    ->label(__('purchases-extensions::request.lines.unit_price'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, Get $get) => self::recalculateInternalLineTotals($set, $get)),
                Hidden::make('product_uom_qty')
                    ->default(1)
                    ->dehydrated(true),
                Hidden::make('price_subtotal')
                    ->dehydrated(true),
                Hidden::make('price_total')
                    ->dehydrated(true),
                Hidden::make('price_tax')
                    ->default(0)
                    ->dehydrated(true),
                Hidden::make('discount')
                    ->default(0)
                    ->dehydrated(true),
            ])
            ->columns(2)
            ->columnSpanFull();
    }

    public static function defaultInternalLineProductId(): ?int
    {
        return once(function (): ?int {
            if (! DatabaseSchema::hasTable('products_products')) {
                return null;
            }

            return Product::query()
                ->where('name', 'General Purchase Item')
                ->where('enable_purchase', true)
                ->value('id');
        });
    }

    public static function defaultInternalLineUomId(): ?int
    {
        return once(function (): ?int {
            $productId = self::defaultInternalLineProductId();

            if (! $productId) {
                return null;
            }

            return Product::query()->whereKey($productId)->value('uom_id');
        });
    }

    public static function defaultMiscSupplierId(): ?int
    {
        return once(function (): ?int {
            if (! DatabaseSchema::hasTable('partners_partners')) {
                return null;
            }

            if (! class_exists(Partner::class)) {
                return null;
            }

            return Partner::query()
                ->where('name', 'Misc Supplier')
                ->value('id');
        });
    }

    /** @return array<int, string> */
    public static function departmentReportMonthOptions(?int $year = null): array
    {
        $year ??= now()->year;

        return collect(range(1, 12))
            ->mapWithKeys(fn (int $month): array => [
                $month => Carbon::createFromDate($year, $month, 1)->translatedFormat('F'),
            ])
            ->all();
    }

    public static function recalculateInternalLineTotals(Set $set, Get $get): void
    {
        $totals = self::calculateInternalLineTotals(
            floatval($get('product_qty') ?? 1),
            floatval($get('price_unit') ?? 0),
        );

        foreach ($totals as $key => $value) {
            $set($key, $value);
        }
    }

    /** @return array{price_subtotal: float, price_tax: int, price_total: float} */
    public static function calculateInternalLineTotals(float $quantity, float $priceUnit): array
    {
        $subTotal = $quantity * $priceUnit;

        return [
            'price_subtotal'   => round($subTotal, 4),
            'price_tax'        => 0,
            'price_total'      => round($subTotal, 4),
        ];
    }

    public static function localizeForm(Schema $schema): Schema
    {
        static::walkFormComponents(
            $schema->getComponents(withHidden: true),
            function (Component $component): void {
                if ($component instanceof FormProgressStepper && $component->getName() === 'state') {
                    $component->label(__('purchases::models/order.log-attributes.state'));
                }

                if ($component instanceof Select && in_array($component->getName(), [
                    'requesting_department_id',
                    'beneficiary_department_id',
                ], true)) {
                    $component->options(fn (): array => static::localizedDepartmentOptions());
                }

                if ($component instanceof Select && $component->getName() === 'currency_id') {
                    $component->default(fn (): ?int => static::defaultOmrCurrencyId());
                }
            },
        );

        return $schema;
    }

    /**
     * @param  array<Component>  $components
     */
    protected static function walkFormComponents(array $components, \Closure $callback): void
    {
        foreach ($components as $component) {
            $callback($component);

            if (! method_exists($component, 'getChildComponents')) {
                continue;
            }

            $children = $component->getChildComponents();

            if ($children === []) {
                continue;
            }

            static::walkFormComponents($children, $callback);
        }
    }

    protected static function resolveRequestType(mixed $requestType): ?RequestType
    {
        if ($requestType instanceof RequestType) {
            return $requestType;
        }

        if (is_string($requestType) && $requestType !== '') {
            return RequestType::tryFrom($requestType);
        }

        return null;
    }
}
