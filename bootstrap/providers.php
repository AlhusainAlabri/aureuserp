<?php

use App\Providers\AppServiceProvider;
use App\Providers\AssetsExtensionsServiceProvider;
use App\Providers\ContactExtensionsServiceProvider;
use App\Providers\DashboardExtensionsServiceProvider;
use App\Providers\EmployeeExtensionsServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\CustomerPanelProvider;
use App\Providers\HrExtensionsServiceProvider;
use App\Providers\InventoryExtensionsServiceProvider;
use App\Providers\ProjectExtensionsServiceProvider;
use App\Providers\PurchaseExtensionsServiceProvider;
use App\Providers\SalesExtensionsServiceProvider;
use Webkul\Account\AccountServiceProvider;
use Webkul\Accounting\AccountingServiceProvider;
use Webkul\Analytic\AnalyticServiceProvider;
use Webkul\Assets\AssetsServiceProvider;
use Webkul\Blog\BlogServiceProvider;
use Webkul\Chatter\ChatterServiceProvider;
use Webkul\Contact\ContactServiceProvider;
use Webkul\Correspondence\CorrespondenceServiceProvider;
use Webkul\DocumentArchive\DocumentArchiveServiceProvider;
use Webkul\Employee\EmployeeServiceProvider;
use Webkul\Field\FieldServiceProvider;
use Webkul\FullCalendar\FullCalendarServiceProvider;
use Webkul\Inventory\InventoryServiceProvider;
use Webkul\Invoice\InvoiceServiceProvider;
use Webkul\Manufacturing\ManufacturingServiceProvider;
use Webkul\Meetings\MeetingsServiceProvider;
use Webkul\MyNotes\MyNotesServiceProvider;
use Webkul\Partner\PartnerServiceProvider;
use Webkul\Payment\PaymentServiceProvider;
use Webkul\Payroll\PayrollServiceProvider;
use Webkul\PluginManager\PluginManagerServiceProvider;
use Webkul\Product\ProductServiceProvider;
use Webkul\Project\ProjectServiceProvider;
use Webkul\Purchase\PurchaseServiceProvider;
use Webkul\Recruitment\RecruitmentServiceProvider;
use Webkul\Sale\SaleServiceProvider;
use Webkul\Security\SecurityServiceProvider;
use Webkul\Support\SupportServiceProvider;
use Webkul\TableViews\TableViewsServiceProvider;
use Webkul\TimeOff\TimeOffServiceProvider;
use Webkul\Timesheet\TimesheetServiceProvider;
use Webkul\Website\WebsiteServiceProvider;

return [
    AppServiceProvider::class,
    HrExtensionsServiceProvider::class,
    InventoryExtensionsServiceProvider::class,
    AssetsExtensionsServiceProvider::class,
    ProjectExtensionsServiceProvider::class,
    DashboardExtensionsServiceProvider::class,
    PurchaseExtensionsServiceProvider::class,
    SalesExtensionsServiceProvider::class,
    AdminPanelProvider::class,
    CustomerPanelProvider::class,
    AccountingServiceProvider::class,
    AccountServiceProvider::class,
    AnalyticServiceProvider::class,
    BlogServiceProvider::class,
    ChatterServiceProvider::class,
    ContactServiceProvider::class,
    ContactExtensionsServiceProvider::class,
    EmployeeExtensionsServiceProvider::class,
    CorrespondenceServiceProvider::class,
    DocumentArchiveServiceProvider::class,
    EmployeeServiceProvider::class,
    FieldServiceProvider::class,
    InventoryServiceProvider::class,
    InvoiceServiceProvider::class,
    ManufacturingServiceProvider::class,
    AssetsServiceProvider::class,
    MeetingsServiceProvider::class,
    MyNotesServiceProvider::class,
    PartnerServiceProvider::class,
    PaymentServiceProvider::class,
    PayrollServiceProvider::class,
    ProductServiceProvider::class,
    ProjectServiceProvider::class,
    PurchaseServiceProvider::class,
    RecruitmentServiceProvider::class,
    SaleServiceProvider::class,
    SecurityServiceProvider::class,
    SupportServiceProvider::class,
    TableViewsServiceProvider::class,
    TimeOffServiceProvider::class,
    FullCalendarServiceProvider::class,
    TimesheetServiceProvider::class,
    WebsiteServiceProvider::class,
    PluginManagerServiceProvider::class,
];
