<?php

namespace App\Filament\Inventory\Pages;

use App\Models\Inventory\InventoryReportArchive;
use App\Services\Inventory\InventoryMovementReportService;
use App\Support\FilamentUrl;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as DbSchema;

class MovementReportArchivesPage extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected string $view = 'filament.inventory.pages.movement-report-archives';

    protected static ?string $slug = 'inventory/movement-report/archives';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static function getPagePermission(): ?string
    {
        return 'page_inventory_movement_report';
    }

    public static function canAccess(array $parameters = []): bool
    {
        return DbSchema::hasTable('inventory_report_archives')
            && parent::canAccess($parameters);
    }

    public function getTitle(): string
    {
        return __('inventory-extensions::archives.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => InventoryReportArchive::query()
                ->where('report_type', 'movement')
                ->with('generatedBy')
                ->latest())
            ->columns([
                TextColumn::make('period_from')
                    ->label(__('inventory-extensions::movement_report.from'))
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('period_to')
                    ->label(__('inventory-extensions::movement_report.to'))
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('file_format')
                    ->label(__('inventory-extensions::archives.format'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                TextColumn::make('generatedBy.name')
                    ->label(__('inventory-extensions::archives.generated_by'))
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label(__('inventory-extensions::pdf.generated_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('download')
                    ->label(__('inventory-extensions::archives.download'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (InventoryReportArchive $record): void {
                        $service = app(InventoryMovementReportService::class);

                        Notification::make()
                            ->success()
                            ->title(__('inventory-extensions::movement_report.exported'))
                            ->actions([
                                Action::make('openDownload')
                                    ->label(__('inventory-extensions::archives.download'))
                                    ->url($service->downloadUrl($record->file_path), shouldOpenInNewTab: true),
                            ])
                            ->send();
                    }),
            ])
            ->emptyStateHeading(__('inventory-extensions::archives.empty.heading'))
            ->emptyStateDescription(__('inventory-extensions::archives.empty.description'))
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToReport')
                ->label(__('inventory-extensions::navigation.movement_report'))
                ->icon('heroicon-o-arrow-left')
                ->url(fn (): string => FilamentUrl::appendLocaleToUrl(MovementReportPage::getUrl()))
                ->color('gray'),
        ];
    }
}
