<?php

namespace App\Filament\Actions\Sales;

use App\Services\Sales\ReturnCreditNote;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Throwable;
use Webkul\Accounting\Filament\Clusters\Customers\Resources\CreditNoteResource as AccountingCreditNoteResource;
use Webkul\Inventory\Models\Operation;
use Webkul\Invoice\Filament\Clusters\Customers\Resources\CreditNoteResource as InvoiceCreditNoteResource;
use Webkul\PluginManager\Package;

/**
 * "Create credit note" on a finished customer return: a draft credit note for exactly the
 * goods that came back and are not credited yet.
 */
class CreateCreditNoteFromReturnAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'sales.return.create-credit-note';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('sales-extensions::messages.credit-note-from-return.label'))
            ->icon('heroicon-o-receipt-refund')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading(__('sales-extensions::messages.credit-note-from-return.heading'))
            ->modalDescription(__('sales-extensions::messages.credit-note-from-return.description'))
            ->visible(fn (Operation $record): bool => app(ReturnCreditNote::class)->canCreate($record))
            ->action(function (Operation $record, $livewire): void {
                try {
                    $creditNotes = app(ReturnCreditNote::class)->create($record);
                } catch (Throwable $e) {
                    Notification::make()->danger()->body($e->getMessage())->send();

                    $this->halt();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title(__('sales-extensions::messages.credit-note-from-return.created', ['count' => $creditNotes->count()]))
                    ->send();

                $resource = Package::isPluginInstalled('accounting') ? AccountingCreditNoteResource::class : InvoiceCreditNoteResource::class;

                $livewire->redirect($resource::getUrl('edit', ['record' => $creditNotes->first()]));
            });
    }
}
