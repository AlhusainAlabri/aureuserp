<?php

namespace App\Services\Accounts;

use App\Mail\InvoiceSentMail;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Webkul\Account\AccountManager;
use Webkul\Account\Models\Move as AccountMove;
use Webkul\Partner\Models\Partner;
use Webkul\Support\Services\EmailService;

class ExtendedAccountManager extends AccountManager
{
    public function printAndSendMove(AccountMove $record, array $data): AccountMove
    {
        $partners = Partner::whereIn('id', $data['partners'])->get();

        $viewTemplate = 'accounts::mail/invoice/actions/invoice';

        foreach ($partners as $partner) {
            if (! $partner->email) {
                continue;
            }

            $attachments = [];

            foreach ($data['files'] ?? [] as $file) {
                $attachments[] = [
                    'path' => $file,
                    'disk' => 'private',
                    'name' => basename($file),
                ];
            }

            try {
                app(EmailService::class)->send(
                    mailClass: InvoiceSentMail::class,
                    view: $viewTemplate,
                    payload: $this->preparePayloadForSendByEmail($record, $partner, $data),
                    attachments: $attachments,
                );
            } catch (Exception $exception) {
                Notification::make()
                    ->danger()
                    ->title(__('invoices-extensions::messages.email-service-error', [
                        'message' => $exception->getMessage(),
                    ]))
                    ->send();

                return $record;
            }
        }

        $messageData = [
            'from' => [
                'company' => Auth::user()->defaultCompany->toArray(),
            ],
            'body' => view($viewTemplate, [
                'payload' => $this->preparePayloadForSendByEmail($record, $partner, $data),
            ])->render(),
            'type' => 'comment',
        ];

        $record->addMessage($messageData, Auth::user()->id);

        Notification::make()
            ->success()
            ->title(__('accounts::filament/resources/invoice/actions/print-and-send.modal.notification.invoice-sent.title'))
            ->body(__('accounts::filament/resources/invoice/actions/print-and-send.modal.notification.invoice-sent.body'))
            ->send();

        return $record;
    }

    /**
     * @return array<string, mixed>
     */
    private function preparePayloadForSendByEmail(AccountMove $record, Partner $partner, array $data): array
    {
        return [
            'record_name' => $record->name,
            'model_name'  => class_basename($record),
            'subject'     => $data['subject'],
            'description' => $data['description'],
            'to'          => [
                'address' => $partner->email,
                'name'    => $partner->name,
            ],
        ];
    }
}
