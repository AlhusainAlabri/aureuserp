<?php

namespace Webkul\Payroll\Filament\Resources\PayslipResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Table;
use Webkul\Payroll\Enums\PayslipStatus;
use Webkul\Payroll\Filament\Resources\PayslipResource;
use Webkul\Payroll\Models\Payslip;

class ListPayslips extends ListRecords
{
    protected static string $resource = PayslipResource::class;

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->emptyStateHeading(__('payroll::payroll.table.empty'));
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('payroll::payroll.tabs.all'))
                ->badge(Payslip::query()->count()),
            'draft' => Tab::make(__('payroll::payroll.tabs.draft'))
                ->badge(Payslip::query()->where('status', PayslipStatus::Draft)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('status', PayslipStatus::Draft)),
            'validated' => Tab::make(__('payroll::payroll.tabs.validated'))
                ->badge(Payslip::query()->where('status', PayslipStatus::Validated)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('status', PayslipStatus::Validated)),
            'paid' => Tab::make(__('payroll::payroll.tabs.paid'))
                ->badge(Payslip::query()->where('status', PayslipStatus::Paid)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('status', PayslipStatus::Paid)),
        ];
    }
}
