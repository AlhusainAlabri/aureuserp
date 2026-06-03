<?php

namespace Webkul\Payroll\Filament\Resources\PayslipResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;
use Webkul\Payroll\Enums\LoanStatus;
use Webkul\Payroll\Enums\PayslipStatus;
use Webkul\Payroll\Filament\Resources\PayslipResource;
use Webkul\Payroll\Models\Loan;
use Webkul\Payroll\Models\Payslip;
use Webkul\Payroll\Services\PayrollCalculator;
use Webkul\Payroll\Services\PayslipPdfService;

class ViewPayslip extends ViewRecord
{
    protected static string $resource = PayslipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recalculate')
                ->label(__('payroll::payroll.payslip.actions.recalculate'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (Payslip $record): bool => auth()->user()?->can('recalculate', $record) ?? false)
                ->requiresConfirmation()
                ->action(function (Payslip $record): void {
                    app(PayrollCalculator::class)->recalculatePayslip($record);

                    Notification::make()
                        ->success()
                        ->title(__('payroll::payroll.payslip.notifications.recalculated.title'))
                        ->send();
                }),
            Action::make('validate')
                ->label(__('payroll::payroll.payslip.actions.validate'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (Payslip $record): bool => auth()->user()?->can('validate', $record) ?? false)
                ->requiresConfirmation()
                ->action(function (Payslip $record): void {
                    $record->update(['status' => PayslipStatus::Validated]);

                    Loan::query()
                        ->where('employee_id', $record->employee_id)
                        ->where('status', LoanStatus::Active)
                        ->each(fn (Loan $loan): float => $loan->deduct($record));

                    Notification::make()
                        ->success()
                        ->title(__('payroll::payroll.payslip.notifications.validated.title'))
                        ->send();
                }),
            Action::make('export_pdf')
                ->label(__('payroll::payroll.payslip.actions.export_pdf'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn (Payslip $record): bool => auth()->user()?->can('exportPdf', $record) ?? false)
                ->action(function (Payslip $record): void {
                    $path = app(PayslipPdfService::class)->generate($record);

                    Notification::make()
                        ->success()
                        ->title(__('payroll::payroll.payslip.notifications.exported.title'))
                        ->body(basename($path))
                        ->send();
                }),
            Action::make('download_pdf')
                ->label(__('payroll::payroll.actions.download'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn (Payslip $record): bool => auth()->user()?->can('exportPdf', $record) ?? false)
                ->action(function (Payslip $record) {
                    $path = app(PayslipPdfService::class)->generate($record);

                    return Storage::disk('private')->download($path);
                }),
        ];
    }
}
