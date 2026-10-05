<?php

use App\Support\Exports\Utf8Csv;
use App\Support\Pdf\ArabicAwarePdf;
use App\Support\Pdf\ArabicText;
use App\Support\Pdf\PdfArabicFont;
use Barryvdh\DomPDF\Facade\Pdf;

it('shapes arabic text into connected presentation forms for dompdf', function (): void {
    $logical = 'تقرير';
    $shaped = ArabicText::shape($logical);

    expect(ArabicText::containsArabic($logical))->toBeTrue()
        ->and($shaped)->not->toBe($logical)
        ->and($shaped)->not->toBe('');
});

it('shapes arabic segments inside html without breaking tags', function (): void {
    $html = '<h1>تقرير حركة المخزون</h1><p class="meta">30 Aug 2026</p>';
    $shaped = ArabicText::shapeHtml($html);

    expect($shaped)->toContain('<h1>')
        ->and($shaped)->toContain('</h1>')
        ->and($shaped)->toContain('class="meta"')
        ->and($shaped)->toContain('30 Aug 2026')
        ->and($shaped)->not->toContain('تقرير حركة المخزون');
});

it('leaves non-arabic html untouched', function (): void {
    $html = '<h1>Inventory Movement Report</h1>';

    expect(ArabicText::shapeHtml($html))->toBe($html);
});

it('binds an arabic-aware dompdf wrapper', function (): void {
    expect(app('dompdf.wrapper'))->toBeInstanceOf(ArabicAwarePdf::class);
});

it('uses the configured arabic pdf font when bundled files exist', function (): void {
    expect(ArabicText::pdfFontFamily())->toBe('Amiri')
        ->and(is_file(resource_path('fonts/amiri/Amiri-Regular.ttf')))->toBeTrue();
});

it('falls back when amiri registration cannot write font metrics', function (): void {
    $readonly = sys_get_temp_dir().'/aureus-readonly-fonts-'.uniqid('', true);
    mkdir($readonly, 0555);

    expect(PdfArabicFont::fontDirIsWritable($readonly))->toBeFalse();

    $dompdf = app('dompdf');
    $dompdf->getOptions()->setFontDir($readonly);

    expect(PdfArabicFont::register($dompdf, 'Amiri'))->toBeFalse();

    $pdf = Pdf::loadHTML('<html><body><h1>تقرير</h1></body></html>')->output();

    expect($pdf)->toStartWith('%PDF');

    chmod($readonly, 0755);
    rmdir($readonly);
});

it('produces a valid pdf when arabic content is rendered through the facade', function (): void {
    app()->setLocale('ar');

    $pdf = Pdf::loadHTML('<html><body><h1>تقرير حركة المخزون</h1></body></html>')
        ->setPaper('a4')
        ->setOption('defaultFont', 'Arial');

    $output = $pdf->output();

    expect($output)->toStartWith('%PDF')
        ->and($pdf->getDomPDF()->getOptions()->getDefaultFont())->toBe(ArabicText::pdfFontFamily())
        ->and($pdf->getDomPDF()->getOptions()->getDefaultFont())->toBe('Amiri');
});

it('prefixes csv output with a utf-8 bom for excel', function (): void {
    $csv = Utf8Csv::fromRows([
        ['التاريخ', 'المنتج'],
        ['2026-09-29', 'صنف تجريبي'],
    ]);

    expect($csv)->toStartWith(Utf8Csv::BOM)
        ->and($csv)->toContain('التاريخ')
        ->and($csv)->toContain('صنف تجريبي');
});
