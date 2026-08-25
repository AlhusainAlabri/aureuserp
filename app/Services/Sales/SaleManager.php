<?php

namespace App\Services\Sales;

use App\Mail\SaleOrderCancelQuotation;
use App\Mail\SaleOrderQuotation;
use Exception;
use Illuminate\Support\Facades\Auth;
use Webkul\Partner\Models\Partner;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Models\Order;
use Webkul\Sale\SaleManager as BaseSaleManager;
use Webkul\Support\Services\EmailService;

class SaleManager extends BaseSaleManager
{
    public function sendByEmail(Order $record, array $data): array
    {
        $partners = Partner::whereIn('id', $data['partners'])->get();

        $sent = [];
        $failed = [];

        foreach ($partners as $partner) {
            if (empty($partner->email)) {
                $failed[$partner->name] = __('sales-extensions::messages.no-email-address');

                continue;
            }

            try {
                $payload = [
                    'record_name' => $record->name,
                    'model_name'  => $record->state->getLabel(),
                    'subject'     => $data['subject'],
                    'description' => $data['description'],
                    'to'          => [
                        'address' => $partner->email,
                        'name'    => $partner->name,
                    ],
                ];

                app(EmailService::class)->send(
                    mailClass: SaleOrderQuotation::class,
                    view: $viewName = 'sales::mails.sale-order-quotation',
                    payload: $payload,
                    attachments: [
                        [
                            'path' => $data['file'],
                            'disk' => 'private',
                            'name' => basename($data['file']),
                        ],
                    ],
                );

                $record->addMessage([
                    'from' => [
                        'company' => Auth::user()->defaultCompany->toArray(),
                    ],
                    'body' => view($viewName, compact('payload'))->render(),
                    'type' => 'comment',
                ]);

                $sent[] = $partner->name;
            } catch (Exception $exception) {
                $failed[$partner->name] = __('sales-extensions::messages.email-service-error', [
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        if (! empty($sent) && $record->state === OrderState::DRAFT) {
            $record->state = OrderState::SENT;
            $record->save();
        }

        return [
            'sent'   => $sent,
            'failed' => $failed,
        ];
    }

    public function cancelAndSendEmail(Order $record, array $data): void
    {
        $partners = Partner::whereIn('id', $data['partners'])->get();

        foreach ($partners as $partner) {
            $payload = [
                'record_name' => $record->name,
                'model_name'  => $record->state->getLabel(),
                'subject'     => $data['subject'],
                'description' => $data['description'],
                'to'          => [
                    'address' => $partner?->email,
                    'name'    => $partner?->name,
                ],
            ];

            app(EmailService::class)->send(
                mailClass: SaleOrderCancelQuotation::class,
                view: $viewName = 'sales::mails.sale-order-cancel-quotation',
                payload: $payload,
            );

            $record->addMessage([
                'from' => [
                    'company' => Auth::user()->defaultCompany->toArray(),
                ],
                'body' => view($viewName, compact('payload'))->render(),
                'type' => 'comment',
            ]);
        }
    }
}
