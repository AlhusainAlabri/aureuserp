<?php

use App\Mail\SaleOrderCancelQuotation;
use App\Mail\SaleOrderQuotation;
use App\Services\Sales\SaleManager;
use App\Support\Sales\SalesOrderPdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Sale\Facades\SaleOrder;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Actions\SendByEmailAction;
use Webkul\Sale\Models\Order;
use Webkul\Support\Models\Currency;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap([
        'sales' => 'sales_orders',
    ]);
});

it('registers the extended sale manager binding', function (): void {
    expect(app('sale'))->toBeInstanceOf(SaleManager::class)
        ->and(SaleOrder::getFacadeRoot())->toBeInstanceOf(SaleManager::class);
});

it('exposes arabic table summarizer translation keys', function (): void {
    app()->setLocale('ar');

    expect(__('sales-extensions::table.summarize.total'))->toBe('الإجمالي')
        ->and(__('sales-extensions::table.summarize.taxes'))->toBe('الضرائب')
        ->and(__('sales-extensions::table.summarize.total-amount'))->toBe('المبلغ الإجمالي');
});

it('uses the app send-by-email action override', function (): void {
    expect((new ReflectionClass(SendByEmailAction::class))->getFileName())
        ->toContain('app/Overrides/Webkul/Sale/Filament/Clusters/Orders/Resources/QuotationResource/Actions/SendByEmailAction.php');
});

it('prefers localized sales views from the app vendor path', function (): void {
    expect(View::getFinder()->find('sales::livewire/quotation-summary'))
        ->toContain('resources/views/vendor/sales/livewire/quotation-summary.blade.php')
        ->and(View::getFinder()->find('sales::sales.quotation'))
        ->toContain('resources/views/vendor/sales/sales/quotation.blade.php');
});

it('renders localized quotation summary labels', function (): void {
    app()->setLocale('ar');

    $html = View::make('sales::livewire/quotation-summary', [
        'subtotal'         => 10,
        'totalTax'         => 1,
        'grandTotal'       => 11,
        'margin'           => 2,
        'marginPercentage' => 20,
        'enableMargin'     => true,
        'currency'         => (object) ['name' => 'OMR'],
    ])->render();

    expect($html)->toContain('المبلغ بدون ضريبة')
        ->and($html)->toContain('الضريبة')
        ->and($html)->toContain('الإجمالي')
        ->and($html)->toContain('الهامش');
});

it('renders localized pdf labels with three decimal places', function (): void {
    app()->setLocale('ar');

    $currency = Currency::query()->firstOrFail();
    $currency->update([
        'decimal_places' => 3,
        'symbol'         => 'ر.ع.',
    ]);

    $order = Order::factory()->create([
        'currency_id'    => $currency->id,
        'amount_untaxed' => 100,
        'amount_tax'     => 5,
        'amount_total'   => 105,
    ]);

    $order->load(['company.country', 'company.state', 'partner.addresses.country', 'partner.addresses.state', 'currency', 'lines.product.uom']);

    $html = View::make('sales::sales.quotation', ['record' => $order])->render();

    expect($html)->toContain('dir="rtl"')
        ->and($html)->toContain('عرض سعر')
        ->and($html)->toContain('المجموع الفرعي')
        ->and($html)->toContain('100.000')
        ->and($html)->toContain('105.000');
});

it('stores sales quotation pdfs on the private disk', function (): void {
    Storage::fake('private');

    $order = Order::factory()->create();

    $path = SalesOrderPdf::store($order, 'pdf-binary');

    expect($path)->toStartWith('sales/')
        ->and(Storage::disk('private')->exists($path))->toBeTrue();
});

it('uses queued mailables for sales order emails', function (): void {
    expect(new SaleOrderQuotation('sales::mails.sale-order-quotation', []))->toBeInstanceOf(ShouldQueue::class)
        ->and(new SaleOrderCancelQuotation('sales::mails.sale-order-cancel-quotation', []))->toBeInstanceOf(ShouldQueue::class);
});

it('returns localized email failure messages from the sale manager', function (): void {
    app()->setLocale('ar');

    $order = Order::factory()->create();
    $partner = $order->partner;
    $partner->update(['email' => null]);

    $result = app(SaleManager::class)->sendByEmail($order, [
        'partners'    => [$partner->id],
        'subject'     => 'Test',
        'description' => 'Body',
        'file'        => 'sales/2026/test.pdf',
    ]);

    expect($result['failed'][$partner->name])->toBe('لا يوجد عنوان بريد إلكتروني');
});

it('uses localized send-by-email defaults in arabic locale', function (): void {
    app()->setLocale('ar');

    expect(__('sales-extensions::send-by-email.subject-default', [
        'partner_name' => 'Acme',
        'reference'    => 'SO-001',
    ]))->toBe('عرض سعر Acme (مرجع SO-001)')
        ->and(__('sales-extensions::send-by-email.description-default', [
            'partner_name' => 'Acme',
            'reference'    => 'SO-001',
            'amount'       => 'ر.ع. 105.000',
        ]))->toContain('Acme')
        ->and(__('sales-extensions::send-by-email.description-default', [
            'partner_name' => 'Acme',
            'reference'    => 'SO-001',
            'amount'       => 'ر.ع. 105.000',
        ]))->toContain('SO-001');
});

it('ensures the sales orders table exists after bootstrap', function (): void {
    expect(Schema::hasTable('sales_orders'))->toBeTrue();
});
