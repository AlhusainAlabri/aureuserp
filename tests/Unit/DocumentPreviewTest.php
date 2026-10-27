<?php

use App\Support\Pdf\DocumentPreview;

it('scopes the print stylesheet to the preview sheet and drops the page wrapper', function (): void {
    $html = '<!DOCTYPE html><html><head><title>x</title><style>@page { margin: 1cm; } body { color: #111; } table { width: 100%; } td, th { text-align: right; }</style></head><body><table><tr><td>A</td></tr></table></body></html>';

    $preview = DocumentPreview::make($html)->toHtml();

    expect($preview)
        ->toContain('<div class="trade-document-preview"><table>')
        ->toContain('.trade-document-preview{ color: #111; }')
        ->toContain('.trade-document-preview table{ width: 100%; }')
        ->toContain('.trade-document-preview td,.trade-document-preview th{ text-align: right; }')
        ->not->toContain('@page')
        ->not->toContain('<html')
        ->not->toContain('<title>');
});

it('gives the preview sheet a white background so the dark theme cannot hide the text', function (): void {
    expect(DocumentPreview::make('<body>x</body>')->toHtml())
        ->toContain('background:#ffffff')
        ->toContain('color:#1f2937');
});
