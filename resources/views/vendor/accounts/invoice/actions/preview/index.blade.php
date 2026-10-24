@include('pdf.trade-document', [
    'docs' => [\App\Support\Pdf\Documents\AccountMoveDocument::make($record, 'invoice')],
])
