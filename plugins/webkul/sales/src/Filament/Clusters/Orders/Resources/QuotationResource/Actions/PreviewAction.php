<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Actions;

use App\Support\Pdf\DocumentPreview;
use Filament\Actions\Action;

class PreviewAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orders.sales.preview-quotation';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('sales::filament/clusters/orders/resources/quotation/actions/preview.title'))
            ->modalFooterActions(fn ($record) => [])
            ->modalContent(fn ($record) => DocumentPreview::make(view('sales::sales.quotation', ['record' => $record])))
            ->color('gray');
    }
}
