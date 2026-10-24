<?php

use App\Support\Rtl\LtrValueFormatter;
use Illuminate\Support\HtmlString;

it('recognises field names that hold left-to-right values', function (string $name, bool $expected): void {
    expect(LtrValueFormatter::appliesTo($name))->toBe($expected);
})->with([
    ['phone', true],
    ['mobile', true],
    ['partner.phone', true],
    ['email', true],
    ['website', true],
    ['sku', true],
    ['reference', true],
    ['invoice_number', true],
    ['barcode', true],
    ['name', false],
    ['description', false],
    ['state', false],
    ['telephone_note', false],
]);

it('isolates latin-only values so the plus sign stays in front', function (): void {
    $formatted = LtrValueFormatter::format('+96824123456');

    expect($formatted)->toBeInstanceOf(HtmlString::class)
        ->and($formatted->toHtml())->toBe('<bdi dir="ltr">+96824123456</bdi>');
});

it('escapes the value it wraps', function (): void {
    expect(LtrValueFormatter::format('a<b>@x.om')->toHtml())->toBe('<bdi dir="ltr">a&lt;b&gt;@x.om</bdi>');
});

it('leaves arabic text, blanks and non-scalar values untouched', function (): void {
    expect(LtrValueFormatter::format('شركة الواحة'))->toBe('شركة الواحة')
        ->and(LtrValueFormatter::format(null))->toBeNull()
        ->and(LtrValueFormatter::format(''))->toBe('')
        ->and(LtrValueFormatter::format(['a']))->toBe(['a']);
});
