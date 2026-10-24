@include('pdf.trade-document', [
    'docs' => [\App\Support\Pdf\Documents\ManufacturingOrderDocument::make($record)],
])
