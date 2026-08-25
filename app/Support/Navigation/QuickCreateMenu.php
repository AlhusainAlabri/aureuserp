<?php

namespace App\Support\Navigation;

use App\Support\FilamentUrl;
use App\Support\SchemaMemo;
use Filament\Resources\Resource;
use Webkul\PluginManager\Package;

class QuickCreateMenu
{
    /**
     * @return list<array{label: string, icon: string, items: list<array{label: string, url: string, icon: string}>}>
     */
    public static function groups(): array
    {
        $groups = [
            static::buildGroup('contacts', 'icon-contacts', [
                static::resourceItem('Webkul\\Contact\\Filament\\Resources\\PartnerResource', 'contact', 'heroicon-o-user'),
            ]),
            static::buildGroup('sales', 'icon-sales', [
                static::resourceItem('Webkul\\Sale\\Filament\\Clusters\\Orders\\Resources\\QuotationResource', 'quotation', 'heroicon-o-document-text'),
                static::resourceItem('Webkul\\Sale\\Filament\\Clusters\\Orders\\Resources\\OrderResource', 'sale_order', 'heroicon-o-shopping-cart'),
                static::resourceItem('Webkul\\Sale\\Filament\\Clusters\\Orders\\Resources\\CustomerResource', 'customer', 'heroicon-o-user-circle'),
            ]),
            static::buildGroup('purchases', 'icon-purchases', [
                static::resourceItem('Webkul\\Purchase\\Filament\\Admin\\Clusters\\Orders\\Resources\\QuotationResource', 'rfq', 'heroicon-o-document-text'),
                static::resourceItem('Webkul\\Purchase\\Filament\\Admin\\Clusters\\Orders\\Resources\\PurchaseOrderResource', 'purchase_order', 'heroicon-o-inbox-arrow-down'),
                static::resourceItem('Webkul\\Purchase\\Filament\\Admin\\Clusters\\Orders\\Resources\\VendorResource', 'vendor', 'heroicon-o-building-storefront'),
            ]),
            static::buildGroup('manufacturing', 'icon-manufacturing', [
                static::resourceItem('Webkul\\Manufacturing\\Filament\\Clusters\\Operations\\Resources\\ManufacturingOrderResource', 'manufacturing_order', 'heroicon-o-cog-6-tooth'),
                static::resourceItem('Webkul\\Manufacturing\\Filament\\Clusters\\Products\\Resources\\BillsOfMaterialResource', 'bill_of_materials', 'heroicon-o-list-bullet'),
            ]),
            static::buildGroup('inventory', 'icon-inventories', [
                static::resourceItem('Webkul\\Inventory\\Filament\\Clusters\\Products\\Resources\\ProductResource', 'product', 'heroicon-o-cube'),
                static::resourceItem('Webkul\\Inventory\\Filament\\Clusters\\Operations\\Resources\\ReceiptResource', 'receipt', 'heroicon-o-arrow-down-tray'),
                static::resourceItem('Webkul\\Inventory\\Filament\\Clusters\\Operations\\Resources\\DeliveryResource', 'delivery', 'heroicon-o-truck'),
            ]),
            static::buildGroup('invoices', 'icon-invoices', [
                static::resourceItem('Webkul\\Invoice\\Filament\\Clusters\\Customers\\Resources\\InvoiceResource', 'invoice', 'heroicon-o-document-text'),
                static::resourceItem('Webkul\\Invoice\\Filament\\Clusters\\Vendors\\Resources\\BillResource', 'bill', 'heroicon-o-document-minus'),
                static::resourceItem('Webkul\\Invoice\\Filament\\Clusters\\Customers\\Resources\\PaymentResource', 'payment', 'heroicon-o-credit-card'),
            ]),
            static::buildGroup('accounting', 'icon-accounting', [
                static::resourceItem('Webkul\\Accounting\\Filament\\Clusters\\Accounting\\Resources\\JournalEntryResource', 'journal_entry', 'heroicon-o-clipboard-document-list'),
                static::resourceItem('Webkul\\Accounting\\Filament\\Clusters\\Customers\\Resources\\InvoiceResource', 'invoice', 'heroicon-o-document-text'),
                static::resourceItem('Webkul\\Accounting\\Filament\\Clusters\\Vendors\\Resources\\BillResource', 'bill', 'heroicon-o-document-minus'),
            ]),
            static::buildGroup('projects', 'icon-projects', [
                static::resourceItem('Webkul\\Project\\Filament\\Resources\\ProjectResource', 'project', 'heroicon-o-folder-open'),
                static::resourceItem('Webkul\\Project\\Filament\\Resources\\TaskResource', 'task', 'heroicon-o-check-circle'),
            ]),
            static::buildGroup('employees', 'icon-employees', [
                static::resourceItem('Webkul\\Employee\\Filament\\Resources\\EmployeeResource', 'employee', 'heroicon-o-identification'),
                static::resourceItem('Webkul\\Employee\\Filament\\Resources\\DepartmentResource', 'department', 'heroicon-o-building-office-2'),
            ]),
            static::buildGroup('time_off', 'icon-time-offs', [
                static::resourceItem('Webkul\\TimeOff\\Filament\\Clusters\\MyTime\\Resources\\MyTimeOffResource', 'time_off', 'heroicon-o-calendar-days'),
                static::resourceItem('Webkul\\TimeOff\\Filament\\Clusters\\MyTime\\Resources\\MyAllocationResource', 'allocation', 'heroicon-o-clock'),
            ]),
            static::buildGroup('recruitment', 'icon-recruitments', [
                static::resourceItem('Webkul\\Recruitment\\Filament\\Clusters\\Applications\\Resources\\CandidateResource', 'candidate', 'heroicon-o-user-plus'),
            ]),
            static::buildGroup('meetings', 'heroicon-o-clipboard-document-list', [
                static::resourceItem(
                    'Webkul\\Meetings\\Filament\\Resources\\MeetingResource',
                    'meeting',
                    'heroicon-o-calendar',
                    table: 'meetings',
                    pluginId: 'meetings',
                ),
            ]),
            static::buildGroup('my_notes', 'heroicon-o-document-text', static::myNotesItems()),
            static::buildGroup('correspondence', 'heroicon-o-envelope', [
                static::resourceItem(
                    'Webkul\\Correspondence\\Filament\\Resources\\CorrespondenceResource',
                    'correspondence',
                    'heroicon-o-envelope-open',
                    table: 'correspondences',
                    pluginId: 'correspondence',
                ),
            ]),
            static::buildGroup('documents', 'heroicon-o-archive-box', [
                static::resourceItem(
                    'Webkul\\DocumentArchive\\Filament\\Resources\\DocFileResource',
                    'document_file',
                    'heroicon-o-document-plus',
                    table: 'doc_files',
                    pluginId: 'document-archive',
                ),
                static::resourceItem(
                    'Webkul\\DocumentArchive\\Filament\\Resources\\DocFolderResource',
                    'document_folder',
                    'heroicon-o-folder-plus',
                    table: 'doc_folders',
                    pluginId: 'document-archive',
                ),
            ]),
        ];

        return array_values(array_filter($groups));
    }

    public static function isVisible(): bool
    {
        return static::groups() !== [];
    }

    /**
     * @param  list<array{label: string, url: string, icon: string}|null>  $items
     * @return array{label: string, icon: string, items: list<array{label: string, url: string, icon: string}>}|null
     */
    protected static function buildGroup(string $groupKey, string $icon, array $items): ?array
    {
        $items = array_values(array_filter($items));

        if ($items === []) {
            return null;
        }

        return [
            'label' => __('admin.quick_create.groups.'.$groupKey),
            'icon'  => $icon,
            'items' => $items,
        ];
    }

    /**
     * @param  class-string<resource>  $resourceClass
     * @return array{label: string, url: string, icon: string}|null
     */
    protected static function resourceItem(
        string $resourceClass,
        string $labelKey,
        string $icon,
        ?string $table = null,
        ?string $pluginId = null,
    ): ?array {
        if (! static::isAvailable($resourceClass, $table, $pluginId)) {
            return null;
        }

        try {
            if (! $resourceClass::canCreate()) {
                return null;
            }

            return [
                'label' => __('admin.quick_create.items.'.$labelKey),
                'url'   => FilamentUrl::appendLocaleToUrl($resourceClass::getUrl('create')),
                'icon'  => $icon,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return list<array{label: string, url: string, icon: string}>
     */
    protected static function myNotesItems(): array
    {
        $pageClass = 'Webkul\\MyNotes\\Filament\\Pages\\MyNotesPage';

        if (! static::isAvailable($pageClass, 'notes', 'my-notes')) {
            return [];
        }

        try {
            if (! $pageClass::canAccess()) {
                return [];
            }
        } catch (\Throwable) {
            return [];
        }

        return collect([
            'text'      => 'heroicon-m-document-text',
            'checklist' => 'heroicon-m-check-circle',
            'reminder'  => 'heroicon-m-bell',
            'voice'     => 'heroicon-m-microphone',
        ])->map(function (string $icon, string $type) use ($pageClass): array {
            return [
                'label' => __('my-notes::notes.types.'.$type),
                'url'   => FilamentUrl::appendLocaleToUrl($pageClass::getUrl(['create' => $type])),
                'icon'  => $icon,
            ];
        })->values()->all();
    }

    /**
     * @param  class-string  $class
     */
    protected static function isAvailable(string $class, ?string $table = null, ?string $pluginId = null): bool
    {
        if (! class_exists($class)) {
            return false;
        }

        if ($table !== null && ! SchemaMemo::hasTable($table)) {
            return false;
        }

        if ($pluginId !== null && ! Package::isPluginInstalled($pluginId)) {
            return false;
        }

        return true;
    }
}
