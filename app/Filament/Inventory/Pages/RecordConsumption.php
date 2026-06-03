<?php

namespace App\Filament\Inventory\Pages;

use App\Services\Inventory\ConsumptionTransferService;
use App\Support\FilamentUrl;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Schema as DbSchema;
use Webkul\Employee\Models\Department;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\InternalResource;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\OperationResource;
use Webkul\Inventory\Models\Location;
use Webkul\Product\Models\Product;
use Webkul\Project\Models\Project;

class RecordConsumption extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected string $view = 'filament.inventory.pages.record-consumption';

    protected static ?string $slug = 'inventory/record-consumption';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-right-circle';

    protected static ?int $navigationSort = 21;

    public ?array $data = [];

    protected static function getPagePermission(): ?string
    {
        return 'page_inventory_record_consumption';
    }

    public static function getNavigationLabel(): string
    {
        return __('inventory-extensions::navigation.record_consumption');
    }

    public static function getNavigationGroup(): string
    {
        return __('inventories::filament/clusters/operations.navigation.group');
    }

    public static function canAccess(array $parameters = []): bool
    {
        if (! DbSchema::hasTable('inventory_consumption_logs')) {
            return false;
        }

        return parent::canAccess($parameters);
    }

    public function getTitle(): string
    {
        return __('inventory-extensions::consumption.title');
    }

    public function mount(): void
    {
        $this->form->fill([
            'quantity'           => 1,
            'department_id'      => null,
            'project_id'         => null,
            'purpose'            => null,
            'source_location_id' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('inventory-extensions::consumption.title'))
                    ->description(__('inventory-extensions::consumption.description'))
                    ->schema([
                        Select::make('product_id')
                            ->label(__('inventory-extensions::consumption.fields.product'))
                            ->options(fn (): array => Product::query()
                                ->where('is_storable', true)
                                ->limit(100)
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Product::query()
                                ->where('is_storable', true)
                                ->where('name', 'like', "%{$search}%")
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->all())
                            ->getOptionLabelUsing(fn ($value): ?string => Product::query()->find($value)?->name)
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('quantity')
                            ->label(__('inventory-extensions::consumption.fields.quantity'))
                            ->numeric()
                            ->required()
                            ->minValue(0.001)
                            ->step(0.001),
                        Select::make('department_id')
                            ->label(__('inventory-extensions::consumption.fields.department'))
                            ->options(fn (): array => Department::query()->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false),
                        Select::make('project_id')
                            ->label(__('inventory-extensions::consumption.fields.project'))
                            ->options(fn (): array => class_exists(Project::class)
                                ? Project::query()->limit(100)->pluck('name', 'id')->all()
                                : [])
                            ->searchable()
                            ->visible(fn (): bool => class_exists(Project::class) && DbSchema::hasTable('projects_projects'))
                            ->native(false),
                        Select::make('source_location_id')
                            ->label(__('inventory-extensions::consumption.fields.source_location'))
                            ->options(fn (): array => Location::query()->limit(200)->pluck('full_name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Textarea::make('purpose')
                            ->label(__('inventory-extensions::consumption.fields.purpose'))
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        $actions = [
            Action::make('backToDashboard')
                ->label(__('inventory-extensions::navigation.dashboard'))
                ->icon('heroicon-o-arrow-left')
                ->url(fn (): string => FilamentUrl::appendLocaleToUrl(InventoryDashboard::getUrl()))
                ->color('gray'),
        ];

        if (! InternalResource::isDiscovered()) {
            return $actions;
        }

        return [
            ...$actions,
            Action::make('bulkTransfer')
                ->label(__('inventory-extensions::consumption.bulk_link'))
                ->icon('heroicon-o-arrows-right-left')
                ->url(fn (): string => FilamentUrl::appendLocaleToUrl(InternalResource::getUrl('index')))
                ->color('gray'),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('submit')
                ->label(__('inventory-extensions::consumption.title'))
                ->action('recordConsumption'),
        ];
    }

    public function recordConsumption(ConsumptionTransferService $service): void
    {
        $data = $this->form->getState();
        $product = Product::query()->findOrFail($data['product_id']);

        try {
            $result = $service->recordConsumption(
                product: $product,
                quantity: (float) $data['quantity'],
                purpose: $data['purpose'],
                departmentId: $data['department_id'] ?? null,
                projectId: $data['project_id'] ?? null,
                sourceLocationId: $data['source_location_id'] ?? null,
            );

            Notification::make()
                ->success()
                ->title(__('inventory-extensions::consumption.notifications.recorded'))
                ->body(__('inventory-extensions::consumption.notifications.recorded_body', [
                    'name' => $result['operation']->name,
                ]))
                ->actions([
                    Action::make('viewOperation')
                        ->label(__('inventory-extensions::dashboard.view_all_movements'))
                        ->url(fn (): string => FilamentUrl::appendLocaleToUrl(
                            OperationResource::getUrl('view', ['record' => $result['operation']->id]),
                        ))
                        ->openUrlInNewTab(),
                ])
                ->send();

            $this->form->fill([
                'quantity'           => 1,
                'department_id'      => $data['department_id'] ?? null,
                'project_id'         => null,
                'purpose'            => null,
                'source_location_id' => null,
            ]);
        } catch (\Throwable $exception) {
            Notification::make()
                ->danger()
                ->title(__('inventory-extensions::consumption.notifications.failed'))
                ->body($exception->getMessage())
                ->send();
        }
    }
}
