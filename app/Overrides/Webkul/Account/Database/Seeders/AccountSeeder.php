<?php

namespace Webkul\Account\Database\Seeders;

use App\Support\Database\Seeders\Accounts\AccountSeederCore;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! Schema::hasTable('accounts_accounts')) {
            return;
        }

        if (DB::table('accounts_accounts')->exists()) {
            return;
        }

        (new AccountSeederCore)->run();
    }
}
