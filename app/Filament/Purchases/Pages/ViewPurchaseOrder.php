<?php

namespace App\Filament\Purchases\Pages;

use App\Filament\Actions\Purchases\ApprovePurchaseWithVoucherAction;
use App\Filament\Traits\HasApprovalActions;
use App\Services\Purchases\PurchasePaymentService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Schema;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource\Pages\ViewOrder;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource;
use Wezlo\FilamentApproval\Actions\CommentAction;
use Wezlo\FilamentApproval\Actions\DelegateAction;
use Wezlo\FilamentApproval\Actions\RejectAction;
use Wezlo\FilamentApproval\Actions\SubmitForApprovalAction;

class ViewPurchaseOrder extends ViewOrder
{
    use HasApprovalActions;

    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return array_merge(
            parent::getHeaderActions(),
            $this->getPurchaseApprovalActions(),
        );
    }

    /**
     * @return array<Action>
     */
    protected function getPurchaseApprovalActions(): array
    {
        return [
            SubmitForApprovalAction::make(),
            ApprovePurchaseWithVoucherAction::make(),
            RejectAction::make(),
            CommentAction::make(),
            DelegateAction::make(),
            Action::make('recordPayment')
                ->label(__('purchases-extensions::request.payment.record_payment'))
                ->icon('heroicon-o-banknotes')
                ->color('primary')
                ->visible(fn (): bool => Schema::hasColumn('purchases_orders', 'amount_paid')
                    && (float) ($this->getRecord()->amount_remaining ?? 0) > 0)
                ->schema([
                    TextInput::make('amount')
                        ->label(__('purchases-extensions::request.payment.amount_paid'))
                        ->numeric()
                        ->required()
                        ->minValue(0.001),
                    DateTimePicker::make('paid_at')
                        ->label(__('purchases-extensions::request.fields.expected_delivery'))
                        ->default(now())
                        ->required(),
                    FileUpload::make('voucher_path')
                        ->label(__('purchases-extensions::request.fields.payment_voucher'))
                        ->disk('private')
                        ->directory(fn (): string => 'purchases/payment-vouchers/'.now()->year),
                    Textarea::make('notes')
                        ->label(__('purchases-extensions::request.fields.justification'))
                        ->rows(2),
                ])
                ->action(function (array $data): void {
                    app(PurchasePaymentService::class)->recordPayment($this->getRecord(), $data);
                    $this->refreshFormData(['amount_paid', 'amount_remaining']);
                }),
        ];
    }
}
