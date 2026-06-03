<?php

namespace App\Filament\Inventory\Widgets;

use App\Filament\Inventory\Pages\MovementReportPage;
use App\Support\FilamentUrl;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Schema;
use Webkul\Inventory\Enums\MoveState;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\OperationResource;
use Webkul\Inventory\Models\Move;

class RecentMovementsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public function getTableHeading(): ?string
    {
        return __('inventory-extensions::dashboard.recent_movements');
    }

    public function table(Table $table): Table
    {
        if (! Schema::hasTable('inventories_moves')) {
            return $table
                ->query(Move::query()->whereRaw('1 = 0'))
                ->columns([]);
        }

        return $table
            ->query(
                Move::query()
                    ->with(['product', 'operation.operationType', 'sourceLocation', 'destinationLocation'])
                    ->where('state', MoveState::DONE)
                    ->latest('updated_at')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('updated_at')
                    ->label(__('inventory-extensions::pdf.date'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('reference')
                    ->label(__('inventory-extensions::pdf.reference'))
                    ->state(fn (Move $record): string => $record->reference ?? $record->operation?->name ?? '—'),
                TextColumn::make('product.name')
                    ->label(__('inventory-extensions::pdf.product'))
                    ->wrap(),
                TextColumn::make('sourceLocation.full_name')
                    ->label(__('inventory-extensions::pdf.source'))
                    ->toggleable(),
                TextColumn::make('destinationLocation.full_name')
                    ->label(__('inventory-extensions::pdf.destination'))
                    ->toggleable(),
                TextColumn::make('product_qty')
                    ->label(__('inventory-extensions::pdf.quantity'))
                    ->numeric(decimalPlaces: 3),
            ])
            ->headerActions([
                Action::make('viewAll')
                    ->label(__('inventory-extensions::dashboard.view_all_movements'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (): string => FilamentUrl::appendLocaleToUrl(MovementReportPage::getUrl()))
                    ->color('gray'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Move $record): string => $record->operation
                        ? FilamentUrl::appendLocaleToUrl(
                            OperationResource::getUrl('view', ['record' => $record->operation]),
                        )
                        : '#')
                    ->visible(fn (Move $record): bool => $record->operation !== null),
            ])
            ->emptyStateHeading(__('inventory-extensions::dashboard.no_movements'))
            ->paginated(false);
    }
}
