<?php

use App\Filament\Extensions\AccountingResourceExtensions;
use Filament\Events\ServingFilament;
use Filament\Facades\Filament;
use Webkul\Account\Models\Account;
use Webkul\Account\Models\Journal;
use Webkul\Accounting\Filament\Clusters\Accounting;
use Webkul\Accounting\Filament\Clusters\Accounting\Resources\JournalEntryResource;
use Webkul\Accounting\Filament\Clusters\Accounting\Resources\JournalEntryResource\Pages\CreateJournalEntry;
use Webkul\Accounting\Filament\Clusters\Customers;
use Webkul\Accounting\Filament\Clusters\Customers\Resources\InvoiceResource;
use Webkul\Accounting\Filament\Clusters\Vendors;
use Webkul\Accounting\Filament\Clusters\Vendors\Resources\BillResource;
use Webkul\Accounting\Filament\Pages\Overview;

it('uses arabic plural labels for accounting journal entries', function (): void {
    app()->setLocale('ar');

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    ServingFilament::dispatch();

    expect(JournalEntryResource::getPluralModelLabel())->toBe('القيود اليومية')
        ->and(JournalEntryResource::getPluralModelLabel())->not->toContain('s');
});

it('uses arabic plural labels for accounting bills and invoices', function (): void {
    app()->setLocale('ar');

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    ServingFilament::dispatch();

    expect(BillResource::getPluralModelLabel())->toBe('الفواتير')
        ->and(InvoiceResource::getPluralModelLabel())->toBe('الفواتير');
});

it('uses arabic cluster breadcrumbs for accounting navigation', function (): void {
    app()->setLocale('ar');

    expect(Accounting::getClusterBreadcrumb())->toBe('المحاسبة')
        ->and(Customers::getClusterBreadcrumb())->toBe('العملاء')
        ->and(Vendors::getClusterBreadcrumb())->toBe('الموردين');
});

it('loads accounting overview title from extensions', function (): void {
    app()->setLocale('ar');

    $page = app(Overview::class);

    expect($page->getTitle())->toBe('نظرة عامة');
});

it('sanitizes empty journal lines before save', function (): void {
    $data = AccountingResourceExtensions::sanitizeJournalLines([
        'lines' => [
            ['account_id' => 1, 'name' => 'Cash', 'debit' => 100],
            ['account_id' => null, 'name' => '', 'debit' => 0],
            ['account_id' => 2, 'name' => 'Automatic Balancing', 'credit' => 100],
        ],
    ]);

    expect($data['lines'])->toHaveCount(2)
        ->and($data['lines'][1]['name'])->toBe(__('accounting-extensions::journal-entry.automatic-balancing'));
});

it('localizes journal names when locale is arabic', function (): void {
    if (! Journal::query()->exists()) {
        $this->markTestSkipped('Journals are not seeded.');
    }

    app()->setLocale('ar');

    $journal = Journal::query()->where('code', 'INV')->first()
        ?? Journal::query()->first();

    expect($journal)->not->toBeNull()
        ->and($journal->name)->not->toBe('Customer Invoices');
});

it('localizes account names when locale is arabic', function (): void {
    if (! Account::query()->exists()) {
        $this->markTestSkipped('Accounts are not seeded.');
    }

    app()->setLocale('ar');

    $account = Account::query()->where('name', 'Cash')->first()
        ?? Account::query()->first();

    expect($account)->not->toBeNull();

    if ($account->name === 'Cash') {
        expect($account->name)->toBe('النقد');
    }
});

it('loads journal entry create page override from the app layer', function (): void {
    expect((new ReflectionClass(CreateJournalEntry::class))->getFileName())
        ->toContain('app/Overrides/Webkul/Accounting/Filament/Clusters/Accounting/Resources/JournalEntryResource/Pages/CreateJournalEntry.php');
});
