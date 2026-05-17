<?php

namespace Webkul\Invoice\Filament\Clusters\Customers\Resources;

use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Webkul\Account\Filament\Resources\InvoiceResource as BaseInvoiceResource;
use Webkul\Invoice\Filament\Clusters\Customers;
use Webkul\Invoice\Filament\Clusters\Customers\Resources\InvoiceResource\Pages\CreateInvoice;
use Webkul\Invoice\Filament\Clusters\Customers\Resources\InvoiceResource\Pages\EditInvoice;
use Webkul\Invoice\Filament\Clusters\Customers\Resources\InvoiceResource\Pages\ListInvoices;
use Webkul\Invoice\Filament\Clusters\Customers\Resources\InvoiceResource\Pages\ManagePayments;
use Webkul\Invoice\Filament\Clusters\Customers\Resources\InvoiceResource\Pages\ViewInvoice;
use Webkul\Invoice\Livewire\InvoiceSummary;
use Webkul\Invoice\Models\Invoice;
use Webkul\Security\Traits\HasResourcePermissionQuery;
use Webkul\Support\Filament\Forms\Components\Repeater;
use Wezlo\FilamentApproval\Columns\ApprovalStatusColumn;
use Wezlo\FilamentApproval\Infolists\ApprovalStatusSection;
use Wezlo\FilamentApproval\RelationManagers\ApprovalsRelationManager;

class InvoiceResource extends BaseInvoiceResource
{
    use HasResourcePermissionQuery;

    protected static ?string $model = Invoice::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static bool $isGloballySearchable = false;

    protected static ?string $cluster = Customers::class;

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('invoices::filament/clusters/customers/resources/invoice.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('invoices::filament/clusters/customers/resources/invoice.navigation.title');
    }

    public static function getSummaryComponent()
    {
        return InvoiceSummary::class;
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewInvoice::class,
            EditInvoice::class,
            ManagePayments::class,
        ]);
    }

    public static function getProductRepeater(): Repeater
    {
        return parent::getProductRepeater()
            ->extraItemActions([
                Action::make('openProduct')
                    ->tooltip('Open product')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (array $arguments, Get $get): ?string => ProductResource::getUrl('edit', [
                        'record' => $get("products.{$arguments['item']}.product_id"),
                    ]))
                    ->openUrlInNewTab()
                    ->visible(fn (array $arguments, Get $get): bool => filled($get("products.{$arguments['item']}.product_id"))),
            ]);
    }

    public static function table(Table $table): Table
    {
        $table = parent::table($table);

        return $table->pushColumns([
            ApprovalStatusColumn::make()
                ->toggleable(isToggledHiddenByDefault: true),
        ]);
    }

    public static function getRelationManagers(): array
    {
        return array_merge(parent::getRelationManagers(), [
            ApprovalsRelationManager::class,
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        $schema = parent::infolist($schema);

        $prop = new \ReflectionProperty(get_class($schema), 'components');
        $prop->setAccessible(true);
        $existing = $prop->getValue($schema);

        return $schema->components([
            ...(is_array($existing) ? $existing : []),
            ApprovalStatusSection::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'    => ListInvoices::route('/'),
            'create'   => CreateInvoice::route('/create'),
            'view'     => ViewInvoice::route('/{record}'),
            'edit'     => EditInvoice::route('/{record}/edit'),
            'payments' => ManagePayments::route('/{record}/payments'),
        ];
    }
}
