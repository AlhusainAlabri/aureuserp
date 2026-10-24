@include('pdf.trade-document', [
    'docs' => collect($records)->map(fn ($order) => \App\Support\Pdf\Documents\PurchaseOrderDocument::make($order, true))->all(),
])
