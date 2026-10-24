@include('pdf.trade-document', [
    'docs' => [\App\Support\Pdf\Documents\SalesOrderDocument::make($record)],
])
