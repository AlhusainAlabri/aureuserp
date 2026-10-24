@include('pdf.trade-document', [
    'docs' => collect($records)->map(fn ($operation) => \App\Support\Pdf\Documents\OperationDocument::make($operation, true))->all(),
])
