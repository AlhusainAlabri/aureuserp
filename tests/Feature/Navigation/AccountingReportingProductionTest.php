<?php

use App\Filament\Accounting\Widgets\JournalChartWidget;
use App\Support\OmrFormatter;
use Webkul\Accounting\Filament\Clusters\Reporting\Pages\Exports\BalanceSheetExport;
use Webkul\Accounting\Filament\Clusters\Reporting\Pages\Exports\ProfitAndLossExport;

it('formats omr amounts with three decimals', function (): void {
    app()->setLocale('ar');

    expect(OmrFormatter::format(100))->toBe('ر.ع. 100.000')
        ->and(OmrFormatter::formatNonZero(0))->toBe('')
        ->and(OmrFormatter::symbol())->toBe('ر.ع.');
});

it('formats omr amounts in english locale', function (): void {
    app()->setLocale('en');

    expect(OmrFormatter::format(10.5))->toBe('OMR 10.500');
});

it('provides arabic reporting translation keys', function (): void {
    app()->setLocale('ar');

    expect(__('accounting-extensions::reporting.net-profit'))->toBe('صافي الربح')
        ->and(__('accounting-extensions::reporting.expand-all'))->toBe('توسيع الكل')
        ->and(__('accounting-extensions::reporting.aged-receivable.title'))->toBe('أعمار الذمم المدينة');
});

it('loads localized accounting export overrides', function (): void {
    expect((new ReflectionClass(BalanceSheetExport::class))->getFileName())
        ->toContain('app/Overrides/Webkul/Accounting/Filament/Clusters/Reporting/Pages/Exports/BalanceSheetExport.php')
        ->and(is_subclass_of(ProfitAndLossExport::class, 'Webkul\\Accounting\\Filament\\Clusters\\Reporting\\Pages\\Exports\\CoreProfitAndLossExport'))
        ->toBeTrue();
});

it('registers localized journal chart widget', function (): void {
    expect(app(JournalChartWidget::class))->toBeInstanceOf(JournalChartWidget::class);
});

it('uses vendor reporting views for profit and loss', function (): void {
    $viewPath = view()->getFinder()->find('accounting::filament.clusters.reporting.pages.profit-loss');

    expect($viewPath)->toContain('resources/views/vendor/accounting/filament/clusters/reporting/pages/profit-loss.blade.php');
});
