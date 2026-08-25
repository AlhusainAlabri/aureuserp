<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Account\Database\Seeders\AccountSeeder;
use Webkul\Support\Database\Seeders\CompanySeeder;
use Webkul\Support\Models\Company;

it('loads the safe company seeder override', function (): void {
    $reflection = new ReflectionClass(CompanySeeder::class);

    expect($reflection->getFileName())->toContain('app/Overrides/Webkul/Support');
});

it('loads the safe account seeder override', function (): void {
    $reflection = new ReflectionClass(AccountSeeder::class);

    expect($reflection->getFileName())->toContain('app/Overrides/Webkul/Account');
});

it('skips company seeder when companies already exist', function (): void {
    if (! Schema::hasTable('companies')) {
        $this->markTestSkipped('Companies table not available.');
    }

    $companyClass = Company::class;
    $existingCount = $companyClass::query()->count();

    (new CompanySeeder)->run();

    expect($companyClass::query()->count())->toBe($existingCount);
});

it('skips account seeder when accounts already exist', function (): void {
    if (! Schema::hasTable('accounts_accounts')) {
        $this->markTestSkipped('Accounts table not available.');
    }

    $existingCount = DB::table('accounts_accounts')->count();

    (new AccountSeeder)->run();

    expect(DB::table('accounts_accounts')->count())->toBe($existingCount);
});
