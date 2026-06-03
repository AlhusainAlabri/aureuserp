<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Resources;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\NumberConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint\Operators\IsRelatedToOperator;
use Filament\Tables\Filters\QueryBuilder\Constraints\SelectConstraint;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Webkul\Inventory\Enums\LocationType;
use Webkul\Inventory\Enums\OrderPointTrigger;
use Webkul\Inventory\Filament\Clusters\Operations;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\ReplenishmentResource\Pages\ManageReplenishment;
use Webkul\Inventory\Models\OrderPoint;
use Webkul\Inventory\Models\Product;
use Webkul\Inventory\Models\Warehouse;

class ReplenishmentResource extends Resource
{
    protected static ?string $model = OrderPoint::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-up-down';

    protected static ?int $navigationSort = 6;

    protected static ?string $cluster = Operations::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getModelLabel(): string
    {
        return __('inventories::models/order-point.title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inventories::models/order-point.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('inventories::filament/clusters/operations/resources/replenishment.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('inventories::filament/clusters/operations/resources/replenishment.navigation.group');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.form.fields.name'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Select::make('product_id')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.product'))
                    ->relationship(
                        name: 'product',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->where('is_storable', true),
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),
                Select::make('warehouse_id')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.form.fields.warehouse'))
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('location_id', null)),
                Select::make('location_id')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.location'))
                    ->relationship(
                        name: 'location',
                        titleAttribute: 'full_name',
                        modifyQueryUsing: fn (Builder $query, callable $get) => $query
                            ->when(
                                $get('warehouse_id'),
                                fn (Builder $q, int $warehouseId) => $q->where('warehouse_id', $warehouseId),
                            )
                            ->where('type', LocationType::INTERNAL),
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('route_id')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.route'))
                    ->relationship('route', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('trigger')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.trigger'))
                    ->options(OrderPointTrigger::class)
                    ->default(OrderPointTrigger::AUTOMATIC)
                    ->required(),
                TextInput::make('product_min_qty')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.min'))
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->minValue(0),
                TextInput::make('product_max_qty')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.max'))
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->minValue(0),
                TextInput::make('qty_multiple')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.multiple-quantity'))
                    ->numeric()
                    ->default(1)
                    ->minValue(1),
                Select::make('company_id')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.company'))
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->default(fn () => Auth::user()?->default_company_id),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('product.name')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.product'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('location.full_name')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.location'))
                    ->sortable(),
                TextColumn::make('route.name')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.route'))
                    ->placeholder('—'),
                TextColumn::make('trigger')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.trigger'))
                    ->badge(),
                TextColumn::make('on_hand')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.on-hand'))
                    ->state(function (OrderPoint $record): float {
                        $product = Product::query()->find($record->product_id);

                        return $product ? (float) $product->available_qty : 0.0;
                    })
                    ->numeric(decimalPlaces: 2)
                    ->color(fn (OrderPoint $record): string => self::isBelowMinimum($record) ? 'danger' : 'success'),
                TextColumn::make('product_min_qty')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.min'))
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('product_max_qty')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.max'))
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('qty_to_order')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.to-order'))
                    ->state(fn (OrderPoint $record): float => self::qtyToOrder($record))
                    ->numeric(decimalPlaces: 2)
                    ->color(fn (OrderPoint $record): string => self::qtyToOrder($record) > 0 ? 'warning' : 'gray'),
                TextColumn::make('product.uom.name')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.uom'))
                    ->placeholder('—'),
                TextColumn::make('company.name')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.company'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->groups([
                Group::make('location.full_name')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.groups.location')),
                Group::make('product.name')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.groups.product')),
                Group::make('product.category.full_name')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.groups.category')),
            ])
            ->filters([
                QueryBuilder::make()
                    ->constraints([
                        RelationshipConstraint::make('product')
                            ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.product'))
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->searchable()
                                    ->preload(),
                            ),
                        SelectConstraint::make('trigger')
                            ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.trigger'))
                            ->options(OrderPointTrigger::class),
                        NumberConstraint::make('product_min_qty')
                            ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.min')),
                    ]),
            ], layout: FiltersLayout::Modal)
            ->filtersTriggerAction(
                fn (Action $action) => $action->slideOver(),
            )
            ->filtersFormColumns(2)
            ->emptyStateHeading(__('inventories::filament/clusters/operations/resources/replenishment.table.empty-state.heading'))
            ->emptyStateDescription(__('inventories::filament/clusters/operations/resources/replenishment.table.empty-state.description'))
            ->headerActions([
                CreateAction::make()
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.header-actions.create.label'))
                    ->icon('heroicon-o-plus-circle')
                    ->mutateDataUsing(function (array $data): array {
                        $data['company_id'] ??= Auth::user()?->default_company_id;

                        if (empty($data['name']) && ! empty($data['product_id'])) {
                            $product = Product::find($data['product_id']);
                            $data['name'] = $product?->name ?? __('inventories::models/order-point.title');
                        }

                        if (empty($data['location_id']) && ! empty($data['warehouse_id'])) {
                            $warehouse = Warehouse::find($data['warehouse_id']);
                            $data['location_id'] = $warehouse?->lot_stock_location_id;
                        }

                        return $data;
                    })
                    ->before(function (CreateAction $action, array $data): void {
                        $exists = OrderPoint::query()
                            ->where('product_id', $data['product_id'])
                            ->where('location_id', $data['location_id'])
                            ->exists();

                        if ($exists) {
                            Notification::make()
                                ->title(__('inventories::filament/clusters/operations/resources/replenishment.table.header-actions.create.before.notification.title'))
                                ->body(__('inventories::filament/clusters/operations/resources/replenishment.table.header-actions.create.before.notification.body'))
                                ->warning()
                                ->send();

                            $action->halt();
                        }
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('inventories::filament/clusters/operations/resources/replenishment.table.header-actions.create.notification.title'))
                            ->body(__('inventories::filament/clusters/operations/resources/replenishment.table.header-actions.create.notification.body')),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function isBelowMinimum(OrderPoint $record): bool
    {
        $product = Product::query()->find($record->product_id);

        if (! $product) {
            return false;
        }

        return (float) $product->available_qty < (float) $record->product_min_qty;
    }

    public static function qtyToOrder(OrderPoint $record): float
    {
        $product = Product::query()->find($record->product_id);

        if (! $product) {
            return 0.0;
        }

        $onHand = (float) $product->available_qty;
        $min = (float) $record->product_min_qty;
        $max = (float) $record->product_max_qty;

        if ($onHand >= $min) {
            return 0.0;
        }

        $target = $max > 0 ? $max : $min;
        $needed = max(0, $target - $onHand);
        $multiple = max(1, (float) $record->qty_multiple);

        return ceil($needed / $multiple) * $multiple;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageReplenishment::route('/'),
        ];
    }
}
