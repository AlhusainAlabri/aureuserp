<?php

namespace App\Filament\Actions\Purchases;

use App\Services\Purchases\PurchasePaymentService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Wezlo\FilamentApproval\Actions\ApproveAction;
use Wezlo\FilamentApproval\Services\ApprovalEngine;

class ApprovePurchaseWithVoucherAction extends ApproveAction
{
    public static function getDefaultName(): ?string
    {
        return 'approve';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('filament-approval::approval.actions.approve'))
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(function (): bool {
                $record = $this->getRecord();
                $approval = $record->currentApproval();

                if (! $approval) {
                    return false;
                }

                $stepInstance = $approval->currentStepInstance();

                if (! $stepInstance) {
                    return false;
                }

                return $stepInstance->canUserAct(auth()->id())
                    && ! $stepInstance->hasUserActed(auth()->id());
            })
            ->schema(function (): array {
                $fields = [
                    Textarea::make('comment')
                        ->label(__('filament-approval::approval.actions.comment_optional'))
                        ->rows(3),
                ];

                if ($this->requiresPaymentVoucher()) {
                    $fields[] = FileUpload::make('payment_voucher')
                        ->label(__('purchases-extensions::request.fields.payment_voucher'))
                        ->disk('private')
                        ->directory(fn () => 'purchases/payment-vouchers/'.now()->year)
                        ->required();
                }

                return $fields;
            })
            ->action(function (array $data): void {
                $record = $this->getRecord();
                $stepInstance = $record->currentApproval()->currentStepInstance();

                if ($this->requiresPaymentVoucher()) {
                    if (empty($data['payment_voucher'])) {
                        Notification::make()
                            ->title(__('purchases-extensions::request.notifications.voucher_required.title'))
                            ->body(__('purchases-extensions::request.notifications.voucher_required.body'))
                            ->danger()
                            ->send();

                        return;
                    }

                    app(PurchasePaymentService::class)->storeFinanceVoucher($record, $data['payment_voucher']);
                }

                app(ApprovalEngine::class)->approve(
                    $stepInstance,
                    auth()->id(),
                    $data['comment'] ?? null,
                );

                Notification::make()
                    ->title(__('filament-approval::approval.actions.approved_success'))
                    ->success()
                    ->send();
            })
            ->requiresConfirmation()
            ->modalHeading(__('filament-approval::approval.actions.approve_heading'));
    }

    protected function requiresPaymentVoucher(): bool
    {
        $record = $this->getRecord();
        $approval = $record->currentApproval();
        $stepInstance = $approval?->currentStepInstance();
        $step = $stepInstance?->step;

        if (! $step) {
            return false;
        }

        $role = $step->approver_config['role'] ?? null;

        return $role === 'finance_manager' && blank($record->payment_voucher_path);
    }
}
