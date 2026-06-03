<?php

namespace Webkul\Payroll\Filament\Resources\PayrollBatchResource\Pages;

use App\Filament\Traits\HasApprovalActions;
use App\Mail\PayslipMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Webkul\Account\Models\Move;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Payroll\Enums\LoanStatus;
use Webkul\Payroll\Enums\PayslipStatus;
use Webkul\Payroll\Filament\Resources\PayrollBatchResource;
use Webkul\Payroll\Models\Loan;
use Webkul\Payroll\Models\PayrollBatch;
use Webkul\Payroll\Services\PayrollAccountingService;
use Webkul\Payroll\Services\PayrollCalculator;
use Webkul\Payroll\Services\PayslipPdfService;
use Webkul\Payroll\Services\WpsExportService;

class ViewPayrollBatch extends ViewRecord
{
    use HasApprovalActions;

    protected static string $resource = PayrollBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->setResource(static::$resource),
            ...$this->getApprovalActions(),
            Action::make('generate')
                ->label(__('payroll::payroll.batch.actions.generate'))
                ->icon('heroicon-o-calculator')
                ->color('primary')
                ->visible(fn (PayrollBatch $record): bool => auth()->user()?->can('generate', $record) ?? false)
                ->requiresConfirmation()
                ->action(function (PayrollBatch $record): void {
                    $batch = app(PayrollCalculator::class)->generateForBatch($record);

                    Notification::make()
                        ->success()
                        ->title(__('payroll::payroll.batch.notifications.generated.title'))
                        ->body(__('payroll::payroll.batch.notifications.generated.body', [
                            'count'  => $batch->employee_count,
                            'period' => sprintf('%02d/%d', $batch->period_month, $batch->period_year),
                        ]))
                        ->send();
                }),
            Action::make('mark_paid')
                ->label(__('payroll::payroll.batch.actions.mark_paid'))
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn (PayrollBatch $record): bool => auth()->user()?->can('markPaid', $record) ?? false)
                ->requiresConfirmation()
                ->action(function (PayrollBatch $record): void {
                    try {
                        $record->markAsPaid();

                        $pdfService = app(PayslipPdfService::class);

                        $record->payslips()->with('employee')->each(function ($payslip) use ($pdfService): void {
                            if ($payslip->status === PayslipStatus::Draft) {
                                $payslip->update(['status' => PayslipStatus::Validated]);
                            }

                            Loan::query()
                                ->where('employee_id', $payslip->employee_id)
                                ->where('status', LoanStatus::Active)
                                ->each(fn (Loan $loan): float => $loan->deduct($payslip));

                            $payslip->update(['status' => PayslipStatus::Paid]);

                            $pdfPath = $pdfService->generate($payslip->fresh(['employee', 'lines', 'batch']));

                            $email = $payslip->employee?->work_email ?? $payslip->employee?->private_email;

                            if ($email) {
                                Mail::to($email)->queue(new PayslipMail($payslip, $pdfPath));
                            }
                        });

                        Notification::make()
                            ->success()
                            ->title(__('payroll::payroll.batch.notifications.paid.title'))
                            ->send();
                    } catch (RuntimeException $exception) {
                        Notification::make()->danger()->title($exception->getMessage())->send();
                    }
                }),
            Action::make('post_to_accounting')
                ->label(__('payroll::payroll.batch.actions.post_to_accounting'))
                ->icon('heroicon-o-book-open')
                ->color('info')
                ->visible(fn (PayrollBatch $record): bool => (auth()->user()?->can('postToAccounting', $record) ?? false)
                    && class_exists(Move::class))
                ->requiresConfirmation()
                ->action(function (PayrollBatch $record): void {
                    if (! class_exists(Move::class)) {
                        Notification::make()
                            ->warning()
                            ->title(__('payroll::payroll.notifications.no_accounting.title'))
                            ->body(__('payroll::payroll.notifications.no_accounting.body'))
                            ->send();

                        return;
                    }

                    try {
                        app(PayrollAccountingService::class)->createDraftJournalEntry($record);
                        $record->markAsPosted();

                        Notification::make()
                            ->success()
                            ->title(__('payroll::payroll.batch.notifications.posted.title'))
                            ->send();
                    } catch (RuntimeException $exception) {
                        Notification::make()->danger()->title($exception->getMessage())->send();
                    }
                }),
            Action::make('export_wps')
                ->label(__('payroll::payroll.batch.actions.export_wps'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->visible(fn (PayrollBatch $record): bool => auth()->user()?->can('exportWps', $record) ?? false)
                ->action(fn (PayrollBatch $record) => app(WpsExportService::class)->export($record)),
            Action::make('export_pdf')
                ->label(__('payroll::payroll.batch.actions.export_pdf'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn (PayrollBatch $record): bool => auth()->user()?->can('exportPdf', $record) ?? false)
                ->action(function (PayrollBatch $record): void {
                    $record->loadMissing(['payslips.employee', 'company']);

                    $pdf = Pdf::loadView('payroll::payroll.pdf.payroll-register', [
                        'batch'    => $record,
                        'payslips' => $record->payslips,
                        'company'  => $record->company,
                    ])->setPaper('a4')->setOption('isRemoteEnabled', true);

                    $path = sprintf(
                        'payroll/pdf/%s/register.pdf',
                        str_replace('/', '-', $record->reference_number),
                    );

                    Storage::disk('private')->put($path, $pdf->output());

                    Notification::make()
                        ->success()
                        ->title(__('payroll::payroll.payslip.notifications.exported.title'))
                        ->send();
                }),
            EditAction::make()
                ->visible(fn (PayrollBatch $record): bool => $record->isDraft()),
        ];
    }
}
