<?php

use App\Support\Pdf\ArabicText;

it('keeps amounts, dates and codes readable inside Arabic text', function (): void {
    expect(ArabicText::shape('ر.ع. 2.350'))->toContain('2.350')->not->toContain('350.2')
        ->and(ArabicText::shape('المجموع 47.000'))->toContain('47.000')->not->toContain('000.47')
        ->and(ArabicText::shape('فاتورة رقم INV/2026/1'))->toContain('INV/2026/1')
        ->and(ArabicText::shape('تاريخ 2026-10-24'))->toContain('2026-10-24');
});

it('lays out words right to left with numbers on the correct side', function (): void {
    // The engine draws left to right, so the first word of the sentence must end up last.
    $shaped = ArabicText::shape('تاريخ 2026-10-24');

    expect(strpos($shaped, '2026-10-24'))->toBeLessThan(mb_strlen($shaped))
        ->and(str_starts_with($shaped, '2026-10-24'))->toBeTrue();
});

it('shapes whole text nodes and leaves styles and tags alone', function (): void {
    $html = ArabicText::shapeHtml('<style>.a{content:"ب"}</style><td>24 أكتوبر 2026</td>');

    expect($html)->toContain('<style>.a{content:"ب"}</style>')
        ->and($html)->toContain('<td>2026 ')
        ->and($html)->toContain(' 24</td>');
});
