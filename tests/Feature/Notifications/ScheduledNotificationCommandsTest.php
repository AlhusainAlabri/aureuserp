<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

it('runs the overdue invoices notification command safely', function (): void {
    if (! Schema::hasTable('accounts_account_moves')) {
        $this->markTestSkipped('Accounts moves table not available.');
    }

    Artisan::call('accounting:notify-overdue-invoices');

    expect(Artisan::output())->toContain('overdue');
});

it('runs the approval sla email notification command safely', function (): void {
    Artisan::call('approval:notify-sla-emails');

    expect(Artisan::output())->toContain('SLA warning');
});
