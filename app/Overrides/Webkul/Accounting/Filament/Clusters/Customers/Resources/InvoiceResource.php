<?php

namespace Webkul\Accounting\Filament\Clusters\Customers\Resources;

use App\Filament\Concerns\LocalizesAccountingResource;
use App\Filament\Extensions\AccountingResourceExtensions;
use App\Filament\Infolists\ApprovalStatusSection;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Webkul\Account\Filament\Resources\InvoiceResource as BaseInvoiceResource;
use Webkul\Accounting\Filament\Clusters\Customers;
use Webkul\Accounting\Filament\Clusters\Customers\Resources\InvoiceResource\Pages\CreateInvoice;
use Webkul\Accounting\Filament\Clusters\Customers\Resources\InvoiceResource\Pages\EditInvoice;
use Webkul\Accounting\Filament\Clusters\Customers\Resources\InvoiceResource\Pages\ListInvoices;
use Webkul\Accounting\Filament\Clusters\Customers\Resources\InvoiceResource\Pages\ManagePayments;
use Webkul\Accounting\Filament\Clusters\Customers\Resources\InvoiceResource\Pages\ViewInvoice;
use Webkul\Accounting\Livewire\InvoiceSummary;
use Webkul\Accounting\Models\Invoice;
use Webkul\Security\Traits\HasResourcePermissionQuery;
use Webkul\Support\Filament\Forms\Components\Repeater;
use Wezlo\FilamentApproval\Columns\ApprovalStatusColumn;
use Wezlo\FilamentApproval\RelationManagers\ApprovalsRelationManager;

class InvoiceResource extends BaseInvoiceResource
{
    use HasResourcePermissionQuery, LocalizesAccountingResource;

    protected static ?string $model = Invoice::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static bool $isGloballySearchable = true;

    protected static ?string $cluster = Customers::class;

    protected static ?int $navigationSort = 1;

    protected static function accountingPluralTranslationKey(): string
    {
        return 'accounting::filament/clusters/customers/resources/invoice';
    }

    public static function getModelLabel(): string
    {
        return static::translatedResourceLabel('singular') ?? __('accounting::filament/clusters/customers/resources/invoice.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::filament/clusters/customers/resources/invoice.navigation.title');
    }

    public static function form(Schema $schema): Schema
    {
        return AccountingResourceExtensions::localizeForm(parent::form($schema));
    }

    public static function getSummaryComponent()
    {
        return InvoiceSummary::class;
    }

    public static function getProductRepeater(): Repeater
    {
        return parent::getProductRepeater()
            ->extraItemActions([
                Action::make('openProduct')
                    ->tooltip(__('accounting-extensions::forms.open-product'))
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (array $arguments, Get $get): ?string => ProductResource::getUrl('edit', [
                        'record' => $get("products.{$arguments['item']}.product_id"),
                    ]))
                    ->openUrlInNewTab()
                    ->visible(fn (array $arguments, Get $get): bool => filled($get("products.{$arguments['item']}.product_id"))),
            ]);
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewInvoice::class,
            EditInvoice::class,
            ManagePayments::class,
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
