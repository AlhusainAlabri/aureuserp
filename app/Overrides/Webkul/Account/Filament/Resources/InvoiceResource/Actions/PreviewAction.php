<?php

namespace Webkul\Account\Filament\Resources\InvoiceResource\Actions;

use App\Support\Invoices\InvoicePdf;
use App\Support\Pdf\DocumentPreview;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Models\Move;

class PreviewAction extends Action
{
    protected string $template = '';

    public static function getDefaultName(): ?string
    {
        return 'customers.invoice.preview';
    }

    public function getTemplate(): string
    {
        return $this->template !== '' ? $this->template : InvoicePdf::viewName();
    }

    public function setTemplate(string $template): static
    {
        if (! view()->exists($template)) {
            throw new InvalidArgumentException("The view [{$template}] does not exist.");
        }

        $this->template = $template;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('accounts::filament/resources/invoice/actions/preview.title'))
            ->color('gray')
            ->visible(fn (Move $record) => $record->state == MoveState::POSTED)
            ->icon('heroicon-o-viewfinder-circle')
            ->modalHeading(__('accounts::filament/resources/invoice/actions/preview.modal.title'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('accounts::filament/resources/invoice/actions/preview.modal.action.close.title'))
            ->modalContent(fn (Move $record) => DocumentPreview::make(view($this->getTemplate(), ['record' => $record])))
            ->modalFooterActions(function (Action $action): array {
                return [
                    $action->getModalCancelAction(),
                    Action::make('print')
                        ->label(__('accounts::filament/resources/invoice/actions/preview.modal.action.print.title'))
                        ->icon('heroicon-o-printer')
                        ->color('primary')
                        ->action(function (Move $record) {
                            $pdfPath = $this->prepareInvoice($record);

                            if ($pdfPath) {
                                return Storage::disk('private')->download($pdfPath, basename($pdfPath));
                            }

                            return null;
                        }),
                ];
            });
    }

    private function prepareInvoice(Move $record): ?string
    {
        return InvoicePdf::store($record, InvoicePdf::generate($record));
    }
}
