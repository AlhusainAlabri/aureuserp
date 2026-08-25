<?php

use App\Filament\Widgets\Dashboard\InvoiceStatusWidget;
use App\Mail\InvoiceSentMail;
use App\Support\Invoices\InvoicePdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Storage;
use Webkul\Account\Filament\Resources\InvoiceResource\Actions\ConfirmAction;
use Webkul\Account\Filament\Resources\InvoiceResource\Actions\PrintAndSendAction;
use Webkul\Account\Livewire\InvoiceSummary;
use Webkul\Account\Models\Move;
use Webkul\Support\Models\Currency;

it('loads invoice action overrides from the app layer', function (): void {
    expect((new ReflectionClass(PrintAndSendAction::class))->getFileName())
        ->toContain('app/Overrides/Webkul/Account')
        ->and((new ReflectionClass(ConfirmAction::class))->getFileName())
        ->toContain('app/Overrides/Webkul/Account');
});

it('loads invoice summary override from the app layer', function (): void {
    expect((new ReflectionClass(InvoiceSummary::class))->getFileName())
        ->toContain('app/Overrides/Webkul/Account');
});

it('uses arabic defaults for print and send email copy', function (): void {
    app()->setLocale('ar');

    expect(__('invoices-extensions::send-by-email.subject-default', [
        'partner_name' => 'عميل',
        'reference'    => 'INV/001',
    ]))->toBe('فاتورة عميل (مرجع INV/001)')
        ->and(__('invoices-extensions::send-by-email.description-default', [
            'partner_name' => 'عميل',
            'reference'    => 'INV/001',
            'company'      => 'شركة',
            'amount'       => 'ر.ع. 10.000',
            'bank'         => 'بنك',
            'sender'       => 'مدير',
        ]))->toContain('عزيزي عميل');
});

it('uses arabic labels in the invoice pdf template', function (): void {
    app()->setLocale('ar');

    $move = Move::factory()->invoice()->make([
        'name'           => 'INV/TEST-001',
        'amount_total'   => 10.5,
        'amount_untaxed' => 10.5,
        'amount_tax'     => 0,
    ]);

    $html = view(InvoicePdf::viewName(), ['record' => $move])->render();

    expect($html)->toContain('dir="rtl"')
        ->and($html)->toContain('فاتورة')
        ->and($html)->toContain('المجموع الفرعي');
});

it('formats invoice amounts with three decimal places by default', function (): void {
    app()->setLocale('ar');

    $move = Move::factory()->invoice()->make([
        'amount_total' => 12.345,
    ]);
    $move->setRelation('currency', new Currency([
        'symbol'         => 'ر.ع.',
        'decimal_places' => 3,
    ]));

    expect(InvoicePdf::formatMoney($move, 12.345))->toBe('ر.ع. 12.345');
});

it('stores generated invoice pdfs on the private disk', function (): void {
    Storage::fake('private');

    $move = Move::factory()->invoice()->make(['name' => 'INV-PDF-001']);
    $path = InvoicePdf::store($move, '%PDF-test%');

    expect($path)->toStartWith('invoices/pdf/')
        ->and(Storage::disk('private')->exists($path))->toBeTrue();
});

it('queues invoice sent mail', function (): void {
    expect(new InvoiceSentMail('accounts::mail/invoice/actions/invoice', [
        'subject' => 'Test',
        'from'    => ['address' => 'a@test.com', 'name' => 'Test'],
    ]))->toBeInstanceOf(ShouldQueue::class);
});

it('uses arabic labels in the invoice summary sidebar', function (): void {
    app()->setLocale('ar');

    expect(__('invoices-extensions::summary.untaxed-amount'))->toBe('المبلغ بدون ضريبة')
        ->and(__('invoices-extensions::summary.amount-due'))->toBe('المبلغ المستحق')
        ->and(__('invoices-extensions::summary.reconcile'))->toBe('إضافة');
});

it('queries invoice_date_due in the dashboard invoice status widget', function (): void {
    $source = (new ReflectionClass(InvoiceStatusWidget::class))->getFileName();
    $contents = file_get_contents($source);

    expect($contents)->toContain('invoice_date_due')
        ->and($contents)->not->toContain('due_date >=');
});

it('loads accounting overview english title from extensions', function (): void {
    app()->setLocale('en');

    expect(__('accounting-extensions::pages.overview'))->toBe('Overview');
});
