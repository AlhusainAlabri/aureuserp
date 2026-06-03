<?php

namespace App\Filament\Inventory\Pages;

use App\Services\Inventory\InventoryMovementReportService;
use App\Support\FilamentUrl;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema as DbSchema;
use Livewire\Attributes\Computed;
use Webkul\Inventory\Enums\MoveState;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\Move;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Product\Models\Product;

class MovementReportPage extends Page implements HasForms, HasTable
{
    use HasPageShield;
    use InteractsWithForms;
    use InteractsWithTable;

    protected string $view = 'filament.inventory.pages.movement-report';

    protected static ?string $slug = 'inventory/movement-report';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?int $navigationSort = 20;

    public ?array $data = [];

    protected static function getPagePermission(): ?string
    {
        return 'page_inventory_movement_report';
    }

    public static function getNavigationLabel(): string
    {
        return __('inventory-extensions::navigation.movement_report');
    }

    public static function getNavigationGroup(): string
    {
        return __('inventories::filament/clusters/operations.navigation.group');
    }

    public static function canAccess(array $parameters = []): bool
    {
        return DbSchema::hasTable('inventories_moves')
            && parent::canAccess($parameters);
    }

    public function getTitle(): string
    {
        return __('inventory-extensions::movement_report.title');
    }

    public function mount(): void
    {
        $this->form->fill([
            'from'         => now()->subDays(30)->format('Y-m-d'),
            'to'           => now()->format('Y-m-d'),
            'warehouse_id' => null,
            'product_id'   => null,
            'location_id'  => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components($this->getFormSchema())
            ->statePath('data');
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make(__('inventory-extensions::movement_report.filters'))
                ->schema([
                    DatePicker::make('from')
                        ->label(__('inventory-extensions::movement_report.from'))
                        ->required()
                        ->native(false)
                        ->live()
                        ->columnSpan(1),
                    DatePicker::make('to')
                        ->label(__('inventory-extensions::movement_report.to'))
                        ->required()
                        ->native(false)
                        ->live()
                        ->columnSpan(1),
                    Select::make('warehouse_id')
                        ->label(__('inventory-extensions::movement_report.warehouse'))
                        ->options(fn (): array => Warehouse::query()->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->placeholder(__('inventory-extensions::movement_report.all'))
                        ->native(false)
                        ->live()
                        ->columnSpan(1),
                    Select::make('product_id')
                        ->label(__('inventory-extensions::movement_report.product'))
                        ->options(fn (): array => Product::query()->where('is_storable', true)->limit(200)->pluck('name', 'id')->all())
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search): array => Product::query()
                            ->where('is_storable', true)
                            ->where('name', 'like', "%{$search}%")
                            ->limit(50)
                            ->pluck('name', 'id')
                            ->all())
                        ->getOptionLabelUsing(fn ($value): ?string => Product::query()->find($value)?->name)
                        ->placeholder(__('inventory-extensions::movement_report.all'))
                        ->native(false)
                        ->live()
                        ->columnSpan(1),
                    Select::make('location_id')
                        ->label(__('inventory-extensions::movement_report.location'))
                        ->options(fn (): array => Location::query()->limit(200)->pluck('full_name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->placeholder(__('inventory-extensions::movement_report.all'))
                        ->native(false)
                        ->live()
                        ->columnSpan(1),
                ])
                ->columns(3),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->movementQuery())
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
                    ->label(__('inventory-extensions::pdf.source')),
                TextColumn::make('destinationLocation.full_name')
                    ->label(__('inventory-extensions::pdf.destination')),
                TextColumn::make('product_qty')
                    ->label(__('inventory-extensions::pdf.quantity'))
                    ->numeric(decimalPlaces: 3),
            ])
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading(__('inventory-extensions::movement_report.empty.heading'))
            ->emptyStateDescription(__('inventory-extensions::movement_report.empty.description'))
            ->emptyStateIcon('heroicon-o-arrows-right-left')
            ->paginated([10, 25, 50]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToDashboard')
                ->label(__('inventory-extensions::navigation.dashboard'))
                ->icon('heroicon-o-arrow-left')
                ->url(fn (): string => FilamentUrl::appendLocaleToUrl(InventoryDashboard::getUrl()))
                ->color('gray'),
            Action::make('viewArchives')
                ->label(__('inventory-extensions::archives.title'))
                ->icon('heroicon-o-archive-box')
                ->url(fn (): string => FilamentUrl::appendLocaleToUrl(MovementReportArchivesPage::getUrl()))
                ->color('gray')
                ->visible(fn (): bool => MovementReportArchivesPage::canAccess()),
            Action::make('exportPdf')
                ->label(__('inventory-extensions::movement_report.export_pdf'))
                ->icon('heroicon-o-document-arrow-down')
                ->action(fn () => $this->exportReport('pdf')),
            Action::make('exportCsv')
                ->label(__('inventory-extensions::movement_report.export_csv'))
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->action(fn () => $this->exportReport('csv')),
        ];
    }

    #[Computed]
    public function reportFilters(): array
    {
        return array_filter([
            'warehouse_id' => $this->data['warehouse_id'] ?? null,
            'product_id'   => $this->data['product_id'] ?? null,
            'location_id'  => $this->data['location_id'] ?? null,
        ], fn ($value): bool => filled($value));
    }

    protected function movementQuery(): Builder
    {
        $from = Carbon::parse($this->data['from'] ?? now()->subDays(30))->startOfDay();
        $to = Carbon::parse($this->data['to'] ?? now())->endOfDay();
        $filters = $this->reportFilters;

        return Move::query()
            ->with(['product', 'operation', 'sourceLocation', 'destinationLocation'])
            ->where('state', MoveState::DONE)
            ->whereBetween('updated_at', [$from, $to])
            ->when(
                filled($filters['product_id'] ?? null),
                fn (Builder $query) => $query->where('product_id', $filters['product_id']),
            )
            ->when(
                filled($filters['location_id'] ?? null),
                fn (Builder $query) => $query->where(function (Builder $query) use ($filters): void {
                    $query->where('source_location_id', $filters['location_id'])
                        ->orWhere('destination_location_id', $filters['location_id']);
                }),
            )
            ->when(
                filled($filters['warehouse_id'] ?? null),
                fn (Builder $query) => $query->where('warehouse_id', $filters['warehouse_id']),
            );
    }

    protected function exportReport(string $format): void
    {
        $service = app(InventoryMovementReportService::class);
        $from = Carbon::parse($this->data['from'])->startOfDay();
        $to = Carbon::parse($this->data['to'])->endOfDay();
        $filters = $this->reportFilters;

        $path = $format === 'csv'
            ? $service->storeCsv($from, $to, $filters)
            : $service->storePdf($from, $to, $filters);

        Notification::make()
            ->success()
            ->title(__('inventory-extensions::movement_report.exported'))
            ->actions([
                Action::make('download')
                    ->label(__('inventory-extensions::movement_report.export_'.$format))
                    ->url($service->downloadUrl($path), shouldOpenInNewTab: true),
            ])
            ->send();
    }

    public function updatedData(): void
    {
        $this->resetTable();
    }
}
