<?php

namespace App\Filament\Purchases\Actions;

use App\Filament\Extensions\PurchaseOrderResourceExtensions;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Webkul\Account\Models\Partner;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Facades\PurchaseOrder;
use Webkul\Purchase\Models\Order;

class SendPurchaseOrderEmailAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'purchases.orders.send-po-email';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(fn (Order $record) => __('purchases::filament/admin/clusters/orders/resources/order/actions/send-po-email.label'))
            ->schema(fn (Order $record) => [
                Select::make('vendors')
                    ->label(__('purchases::filament/admin/clusters/orders/resources/order/actions/send-po-email.form.fields.to'))
                    ->options(fn () => Partner::get()->mapWithKeys(fn ($partner) => [
                        $partner->id => $partner->email
                            ? "{$partner->name} <{$partner->email}>"
                            : $partner->name,
                    ])->toArray())
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->default([$record->partner_id]),

                TextInput::make('subject')
                    ->label(__('purchases::filament/admin/clusters/orders/resources/order/actions/send-po-email.form.fields.subject'))
                    ->required()
                    ->default(__('purchases-extensions::request.vendor_email.po.subject', [
                        'reference' => $record->name,
                    ])),

                MarkdownEditor::make('message')
                    ->label(__('purchases::filament/admin/clusters/orders/resources/order/actions/send-po-email.form.fields.message'))
                    ->required()
                    ->default(function () use ($record): string {
                        $plannedAt = $record->planned_at
                            ? $record->planned_at->translatedFormat('d M Y')
                            : __('purchases-extensions::request.vendor_email.po.no_planned_date');

                        return __('purchases-extensions::request.vendor_email.po.body', [
                            'vendor'     => $record->partner->name,
                            'reference'  => $record->name,
                            'amount'     => PurchaseOrderResourceExtensions::formatOmrAmount($record->total_amount),
                            'planned_at' => $plannedAt,
                            'sender'     => Auth::user()->name,
                        ]);
                    }),

                FileUpload::make('attachment')
                    ->hiddenLabel()
                    ->disk('private')
                    ->default(fn () => PurchaseOrder::generatePurchaseOrderPdf($record))
                    ->acceptedFileTypes([
                        'image/*',
                        'application/pdf',
                    ])
                    ->downloadable()
                    ->openable(),
            ])
            ->action(function (array $data, Order $record, Component $livewire): void {
                try {
                    $record = PurchaseOrder::sendPurchaseOrder($record, $data);
                } catch (Exception $e) {
                    Notification::make()
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                $livewire->updateForm();

                Notification::make()
                    ->title(__('purchases::filament/admin/clusters/orders/resources/order/actions/send-po-email.action.notification.success.title'))
                    ->body(__('purchases::filament/admin/clusters/orders/resources/order/actions/send-po-email.action.notification.success.body'))
                    ->success()
                    ->send();
            })
            ->visible(fn (Order $record) => $record->state === OrderState::PURCHASE);
    }
}
