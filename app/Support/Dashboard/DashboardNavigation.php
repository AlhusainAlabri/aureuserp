<?php

namespace App\Support\Dashboard;

use App\Filament\Assets\Pages\PendingBorrowingRequests;
use App\Filament\Projects\Pages\TaskOperationsHub;
use App\Support\FilamentUrl;
use App\Support\SchemaMemo;
use Webkul\Accounting\Filament\Clusters\Customers\Resources\InvoiceResource;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource;
use Webkul\Employee\Filament\Resources\EmployeeResource;
use Webkul\Invoice\Filament\Clusters\Customers\Resources\InvoiceResource as CustomerInvoiceResource;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Payroll\Filament\Resources\PayrollBatchResource;
use Webkul\PluginManager\Package;
use Webkul\Project\Filament\Resources\ProjectResource;
use Webkul\Project\Filament\Resources\TaskResource;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource;
use Wezlo\FilamentApproval\Resources\ApprovalFlowResource;

class DashboardNavigation
{
    public static function meetingApprovalsUrl(): ?string
    {
        if (! self::meetingsAreAvailable()) {
            return null;
        }

        return FilamentUrl::appendLocaleToUrl(
            MeetingResource::getUrl('index', ['activeTab' => 'pending_approval']),
        );
    }

    public static function correspondenceApprovalsUrl(): ?string
    {
        if (! self::correspondenceIsAvailable()) {
            return null;
        }

        return FilamentUrl::appendLocaleToUrl(
            CorrespondenceResource::getUrl('index'),
        );
    }

    public static function purchaseOrderApprovalsUrl(): ?string
    {
        if (! class_exists(PurchaseOrderResource::class) || ! SchemaMemo::hasTable('purchases_orders')) {
            return null;
        }

        return FilamentUrl::appendLocaleToUrl(
            PurchaseOrderResource::getUrl('index'),
        );
    }

    public static function invoiceApprovalsUrl(): ?string
    {
        if (class_exists(CustomerInvoiceResource::class)) {
            return FilamentUrl::appendLocaleToUrl(
                CustomerInvoiceResource::getUrl('index'),
            );
        }

        if (class_exists(InvoiceResource::class)) {
            return FilamentUrl::appendLocaleToUrl(
                InvoiceResource::getUrl('index'),
            );
        }

        return null;
    }

    public static function payrollBatchApprovalsUrl(): ?string
    {
        if (! class_exists(PayrollBatchResource::class) || ! SchemaMemo::hasTable('payroll_batches')) {
            return null;
        }

        return FilamentUrl::appendLocaleToUrl(
            PayrollBatchResource::getUrl('index'),
        );
    }

    public static function assetBorrowingApprovalsUrl(): ?string
    {
        if (! SchemaMemo::hasTable('asset_borrowings') || ! class_exists(PendingBorrowingRequests::class)) {
            return null;
        }

        return FilamentUrl::appendLocaleToUrl(
            PendingBorrowingRequests::getUrl(),
        );
    }

    public static function approvalFlowsUrl(): ?string
    {
        if (! class_exists(ApprovalFlowResource::class) || ! SchemaMemo::hasTable('approval_flows')) {
            return null;
        }

        return FilamentUrl::appendLocaleToUrl(
            ApprovalFlowResource::getUrl('index'),
        );
    }

    public static function meetingsIndexUrl(): ?string
    {
        if (! self::meetingsAreAvailable()) {
            return null;
        }

        return FilamentUrl::appendLocaleToUrl(
            MeetingResource::getUrl('index'),
        );
    }

    public static function correspondenceIndexUrl(): ?string
    {
        if (! self::correspondenceIsAvailable()) {
            return null;
        }

        return FilamentUrl::appendLocaleToUrl(
            CorrespondenceResource::getUrl('index'),
        );
    }

    public static function taskOperationsHubUrl(): ?string
    {
        if (! SchemaMemo::hasTable('projects_tasks') || ! TaskOperationsHub::canAccess()) {
            return null;
        }

        return FilamentUrl::appendLocaleToUrl(TaskOperationsHub::getUrl());
    }

    public static function projectTasksUrl(): ?string
    {
        if (! class_exists(TaskResource::class)) {
            return null;
        }

        return FilamentUrl::appendLocaleToUrl(
            TaskResource::getUrl('index'),
        );
    }

    public static function activeProjectsUrl(): ?string
    {
        if (! class_exists(ProjectResource::class)) {
            return null;
        }

        return FilamentUrl::appendLocaleToUrl(
            ProjectResource::getUrl('index'),
        );
    }

    public static function expiringDocumentsUrl(): ?string
    {
        if (! class_exists(EmployeeResource::class)) {
            return null;
        }

        return FilamentUrl::appendLocaleToUrl(
            EmployeeResource::getUrl('index'),
        );
    }

    protected static function correspondenceIsAvailable(): bool
    {
        return class_exists(CorrespondenceResource::class)
            && SchemaMemo::hasTable('correspondences')
            && Package::isPluginInstalled('correspondence');
    }

    protected static function meetingsAreAvailable(): bool
    {
        return class_exists(MeetingResource::class)
            && SchemaMemo::hasTable('meetings')
            && Package::isPluginInstalled('meetings');
    }
}
