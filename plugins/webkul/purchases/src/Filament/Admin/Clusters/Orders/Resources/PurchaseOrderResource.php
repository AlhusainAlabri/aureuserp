<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Filament\Admin\Clusters\Orders;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource\Pages\CreatePurchaseOrder;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource\Pages\EditPurchaseOrder;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource\Pages\ListPurchaseOrders;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource\Pages\ManageBills;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource\Pages\ManageReceipts;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource\Pages\ViewPurchaseOrder;
use Webkul\Purchase\Models\PurchaseOrder;
use Wezlo\FilamentApproval\Columns\ApprovalStatusColumn;
use Wezlo\FilamentApproval\Infolists\ApprovalStatusSection;
use Wezlo\FilamentApproval\RelationManagers\ApprovalsRelationManager;

class PurchaseOrderResource extends OrderResource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static bool $shouldRegisterNavigation = true;

    protected static bool $isGloballySearchable = true;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = Orders::class;

    public static function getNavigationLabel(): string
    {
        return __('purchases::filament/admin/clusters/orders/resources/purchase-order.navigation.title');
    }

    public static function getModelLabel(): string
    {
        return __('purchases::filament/admin/clusters/orders/resources/purchase-order.navigation.title');
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewPurchaseOrder::class,
            EditPurchaseOrder::class,
            ManageBills::class,
            ManageReceipts::class,
        ]);
    }

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('state', [OrderState::PURCHASE, OrderState::DONE]))
            ->columns([
                ApprovalStatusColumn::make()
                    ->label(__('purchases::filament/admin/clusters/orders/resources/purchase-order.table.columns.approval-status')),
                ...parent::table($table)->getColumns(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                ApprovalStatusSection::make(),
                Section::make(__('purchases::filament/admin/clusters/orders/resources/purchase-order.infolist.sections.receipt.title'))
                    ->icon('heroicon-o-document-text')
                    ->visible(fn ($record): bool => $record->isReceiptRequired())
                    ->schema([
                        TextEntry::make('receipt_uploaded')
                            ->hiddenLabel()
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state
                                ? __('purchases::filament/admin/clusters/orders/resources/purchase-order.infolist.sections.receipt.entries.uploaded')
                                : __('purchases::filament/admin/clusters/orders/resources/purchase-order.infolist.sections.receipt.entries.missing')
                            )
                            ->color(fn (bool $state): string => $state ? 'success' : 'warning'),
                        TextEntry::make('receipt_uploaded_at')
                            ->label(__('purchases::filament/admin/clusters/orders/resources/purchase-order.infolist.sections.receipt.entries.uploaded-at'))
                            ->dateTime()
                            ->placeholder('—')
                            ->visible(fn ($record): bool => $record->receipt_uploaded),
                        TextEntry::make('receipt_path')
                            ->hiddenLabel()
                            ->formatStateUsing(fn (): string => '')
                            ->prefixAction(
                                Action::make('downloadReceipt')
                                    ->label(__('purchases::filament/admin/clusters/orders/resources/purchase-order.infolist.sections.receipt.actions.download'))
                                    ->icon('heroicon-o-arrow-down-tray')
                                    ->color('primary')
                                    ->visible(fn ($record): bool => $record->receipt_uploaded && filled($record->receipt_path))
                                    ->action(function ($record) {
                                        return Storage::disk('private')->download($record->receipt_path);
                                    })
                            )
                            ->suffixAction(
                                Action::make('uploadReceipt')
                                    ->label(__('purchases::filament/admin/clusters/orders/resources/purchase-order.infolist.sections.receipt.actions.upload'))
                                    ->icon('heroicon-o-arrow-up-tray')
                                    ->color('warning')
                                    ->visible(fn ($record): bool => ! $record->receipt_uploaded)
                                    ->form([
                                        FileUpload::make('receipt_file')
                                            ->label(__('purchases::filament/admin/clusters/orders/resources/purchase-order.infolist.sections.receipt.form.fields.receipt-file'))
                                            ->disk('private')
                                            ->directory(fn ($record) => 'purchases/receipts/'.now()->year)
                                            ->required(),
                                    ])
                                    ->action(function (array $data, $record): void {
                                        $record->update([
                                            'receipt_path'        => $data['receipt_file'],
                                            'receipt_uploaded'    => true,
                                            'receipt_uploaded_at' => now(),
                                        ]);

                                        Notification::make()
                                            ->success()
                                            ->title(__('purchases::filament/admin/clusters/orders/resources/purchase-order.infolist.sections.receipt.notifications.upload-success.title'))
                                            ->body(__('purchases::filament/admin/clusters/orders/resources/purchase-order.infolist.sections.receipt.notifications.upload-success.body'))
                                            ->send();
                                    }),
                            ),
                    ]),
                ...parent::infolist($schema)->getComponents(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ApprovalsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'    => ListPurchaseOrders::route('/'),
            'create'   => CreatePurchaseOrder::route('/create'),
            'view'     => ViewPurchaseOrder::route('/{record}'),
            'edit'     => EditPurchaseOrder::route('/{record}/edit'),
            'bills'    => ManageBills::route('/{record}/bills'),
            'receipts' => ManageReceipts::route('/{record}/receipts'),
        ];
    }
}
