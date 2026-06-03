<?php

namespace App\Filament\Inventory\Pages;

use App\Enums\Inventory\DefaultProcurement;
use App\Enums\Purchases\RequestType;
use App\Models\Inventory\InventoryReplenishmentPreference;
use App\Services\Inventory\ReplenishmentProcurementService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\ReplenishmentResource;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\ReplenishmentResource\Pages\ManageReplenishment as BaseManageReplenishment;
use Webkul\Inventory\Models\OrderPoint;
use Webkul\TableViews\Filament\Components\PresetView;

class ManageReplenishment extends BaseManageReplenishment
{
    public function getPresetTableViews(): array
    {
        return [
            ...parent::getPresetTableViews(),
            'below_minimum' => PresetView::make(__('inventory-extensions::replenishment.tabs.below_minimum'))
                ->favorite()
                ->icon('heroicon-s-exclamation-triangle')
                ->modifyQueryUsing(function (Builder $query): Builder {
                    $ids = OrderPoint::query()
                        ->with('product')
                        ->get()
                        ->filter(fn (OrderPoint $point): bool => ReplenishmentResource::isBelowMinimum($point))
                        ->pluck('id');

                    if ($ids->isEmpty()) {
                        return $query->whereRaw('1 = 0');
                    }

                    return $query->whereIn('id', $ids);
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->columns([
                ...$table->getColumns(),
                TextColumn::make('procurement_preference')
                    ->label(__('inventory-extensions::replenishment.columns.procurement'))
                    ->state(function (OrderPoint $record): string {
                        $preference = InventoryReplenishmentPreference::query()
                            ->where('order_point_id', $record->id)
                            ->first();

                        return ($preference?->default_procurement ?? DefaultProcurement::InternalRequest)->getLabel();
                    })
                    ->badge(),
            ])
            ->recordActions([
                ...$table->getRecordActions(),
                Action::make('createInternalRequest')
                    ->label(__('inventory-extensions::replenishment.actions.internal_request'))
                    ->icon('heroicon-o-document-text')
                    ->visible(fn (OrderPoint $record): bool => ReplenishmentResource::qtyToOrder($record) > 0)
                    ->schema([
                        Select::make('request_type')
                            ->label(__('purchases-extensions::request.fields.request_type'))
                            ->options(collect(RequestType::cases())
                                ->filter(fn (RequestType $type): bool => in_array($type->value, RequestType::internalRequestTypes(), true))
                                ->mapWithKeys(fn (RequestType $type): array => [$type->value => $type->getLabel()])
                                ->all())
                            ->default(fn (OrderPoint $record): string => InventoryReplenishmentPreference::query()
                                ->where('order_point_id', $record->id)
                                ->value('default_request_type') ?? RequestType::OfficeSupplies->value)
                            ->required()
                            ->native(false),
                    ])
                    ->action(function (OrderPoint $record, array $data, ReplenishmentProcurementService $service): void {
                        $order = $service->createInternalRequest(
                            $record,
                            RequestType::from($data['request_type']),
                        );

                        Notification::make()
                            ->success()
                            ->title(__('inventory-extensions::replenishment.notifications.internal_request_created'))
                            ->send();

                        $this->redirect($service->editOrderUrl($order));
                    }),
                Action::make('createDraftPo')
                    ->label(__('inventory-extensions::replenishment.actions.draft_po'))
                    ->icon('heroicon-o-shopping-cart')
                    ->color('warning')
                    ->visible(fn (OrderPoint $record): bool => ReplenishmentResource::qtyToOrder($record) > 0)
                    ->requiresConfirmation()
                    ->action(function (OrderPoint $record, ReplenishmentProcurementService $service): void {
                        $order = $service->createDraftPurchaseOrder($record);

                        Notification::make()
                            ->success()
                            ->title(__('inventory-extensions::replenishment.notifications.draft_po_created'))
                            ->send();

                        $this->redirect($service->editOrderUrl($order));
                    }),
                Action::make('editProcurementPreference')
                    ->label(__('inventory-extensions::replenishment.actions.preference'))
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Select::make('default_procurement')
                            ->label(__('inventory-extensions::replenishment.fields.default_procurement'))
                            ->options(DefaultProcurement::class)
                            ->required()
                            ->native(false),
                        Select::make('default_request_type')
                            ->label(__('inventory-extensions::replenishment.fields.default_request_type'))
                            ->options(collect(RequestType::cases())
                                ->filter(fn (RequestType $type): bool => in_array($type->value, RequestType::internalRequestTypes(), true))
                                ->mapWithKeys(fn (RequestType $type): array => [$type->value => $type->getLabel()])
                                ->all())
                            ->native(false),
                    ])
                    ->fillForm(function (OrderPoint $record): array {
                        $preference = app(ReplenishmentProcurementService::class)->preferenceFor($record);

                        return [
                            'default_procurement'  => $preference->default_procurement->value,
                            'default_request_type' => $preference->default_request_type?->value ?? RequestType::OfficeSupplies->value,
                        ];
                    })
                    ->action(function (OrderPoint $record, array $data): void {
                        InventoryReplenishmentPreference::query()->updateOrCreate(
                            ['order_point_id' => $record->id],
                            [
                                'default_procurement'  => $data['default_procurement'],
                                'default_request_type' => $data['default_request_type'] ?? null,
                            ],
                        );

                        Notification::make()
                            ->success()
                            ->title(__('inventory-extensions::replenishment.notifications.preference_saved'))
                            ->send();
                    }),
            ])
            ->toolbarActions([
                ...$table->getToolbarActions(),
                BulkActionGroup::make([
                    BulkAction::make('bulkInternalRequest')
                        ->label(__('inventory-extensions::replenishment.actions.bulk_internal_request'))
                        ->icon('heroicon-o-document-text')
                        ->requiresConfirmation()
                        ->action(function (Collection $records, ReplenishmentProcurementService $service): void {
                            $created = 0;

                            foreach ($records as $record) {
                                if (! $record instanceof OrderPoint || ReplenishmentResource::qtyToOrder($record) <= 0) {
                                    continue;
                                }

                                $service->createInternalRequest($record);
                                $created++;
                            }

                            Notification::make()
                                ->success()
                                ->title(__('inventory-extensions::replenishment.notifications.bulk_created', ['count' => $created]))
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('bulkDraftPo')
                        ->label(__('inventory-extensions::replenishment.actions.bulk_draft_po'))
                        ->icon('heroicon-o-shopping-cart')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records, ReplenishmentProcurementService $service): void {
                            $created = 0;

                            foreach ($records as $record) {
                                if (! $record instanceof OrderPoint || ReplenishmentResource::qtyToOrder($record) <= 0) {
                                    continue;
                                }

                                $service->createDraftPurchaseOrder($record);
                                $created++;
                            }

                            Notification::make()
                                ->success()
                                ->title(__('inventory-extensions::replenishment.notifications.bulk_created', ['count' => $created]))
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
