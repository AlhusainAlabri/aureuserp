<?php

namespace Webkul\Account\Filament\Resources\InvoiceResource\Actions;

use App\Support\Invoices\InvoicePdf;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Facades\Account as AccountFacade;
use Webkul\Account\Models\Move;
use Webkul\Partner\Models\Partner;

class PrintAndSendAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'customers.invoice.print-and-send';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('accounts::filament/resources/invoice/actions/print-and-send.title'))
            ->color('gray')
            ->visible(function (Move $record) {
                return
                    $record->state == MoveState::CANCEL
                    || $record->state == MoveState::POSTED;
            });

        $this->beforeFormFilled(function (Move $record, Action $action): void {
            $filePath = InvoicePdf::store($record, InvoicePdf::generate($record));

            $action->fillForm([
                'files'       => [$filePath],
                'partners'    => [$record->partner_id],
                'subject'     => __('invoices-extensions::send-by-email.subject-default', [
                    'partner_name' => $record->partner->name,
                    'reference'    => $record->name,
                ]),
                'description' => __('invoices-extensions::send-by-email.description-default', [
                    'partner_name' => $record->partner->name,
                    'reference'    => $record->name,
                    'company'      => $record->company->name,
                    'amount'       => InvoicePdf::formatAmount($record),
                    'bank'         => $record->partnerBank?->bank?->name ?? __('invoices-extensions::pdf.not-available'),
                    'sender'       => Auth::user()?->name ?? config('app.name'),
                ]),
            ]);
        });

        $this->schema(
            function (Schema $schema) {
                return $schema->components([
                    Select::make('partners')
                        ->options(Partner::all()->pluck('name', 'id'))
                        ->multiple()
                        ->label(__('accounts::filament/resources/invoice/actions/print-and-send.modal.form.partners'))
                        ->searchable()
                        ->preload(),
                    TextInput::make('subject')
                        ->label(__('accounts::filament/resources/invoice/actions/print-and-send.modal.form.subject'))
                        ->hiddenLabel(),
                    RichEditor::make('description')
                        ->label(__('accounts::filament/resources/invoice/actions/print-and-send.modal.form.description'))
                        ->hiddenLabel(),
                    FileUpload::make('files')
                        ->label(__('accounts::filament/resources/invoice/actions/print-and-send.modal.form.files'))
                        ->acceptedFileTypes([
                            'image/*',
                            'application/pdf',
                        ])
                        ->downloadable()
                        ->openable()
                        ->multiple()
                        ->disk('private')
                        ->visibility('private')
                        ->hiddenLabel(),
                ]);
            }
        );

        $this->modalSubmitActionLabel(__('accounts::filament/resources/invoice/actions/print-and-send.modal.action.submit.title'));
        $this->modalIcon('heroicon-m-paper-airplane');
        $this->icon('heroicon-o-envelope');
        $this->action(fn (Move $record, array $data) => AccountFacade::printAndSendMove($record, $data));
        $this->modalSubmitAction(function ($action) {
            $action->label(__('accounts::filament/resources/invoice/actions/print-and-send.modal.action.submit.title'));
            $action->icon('heroicon-m-paper-airplane');
        });
    }
}
