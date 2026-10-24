<?php

namespace Webkul\Support\Database\Seeders;

use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (
            ! Schema::hasTable('users')
            || ! Schema::hasTable('companies')
            || ! Schema::hasTable('partners_partners')
        ) {
            return;
        }

        if (Company::query()->exists() || User::query()->exists()) {
            return;
        }

        DB::beginTransaction();

        try {
            $user = User::first();

            $name = (string) env('ERP_COMPANY_NAME', config('app.name'));

            $partnerId = DB::table('partners_partners')->insertGetId([
                'sub_type'   => 'company',
                'name'       => $name,
                'creator_id' => $user?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $currency = Currency::find(1);

            if (! $currency) {
                throw new Exception('Currency with ID 1 not found.');
            }

            DB::table('companies')->insert([
                'sort'        => 1,
                'name'        => $name,
                'creator_id'  => $user?->id,
                'is_active'   => true,
                'currency_id' => $currency->id,
                'partner_id'  => $partnerId,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
        }
    }
}
