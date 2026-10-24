<?php

use App\Services\Install\SeedDataLocalizer;
use App\Services\Install\StateProfile;
use Illuminate\Support\Facades\DB;

it('adds the governorates of Oman when the country has none', function (): void {
    $omanId = DB::table('countries')->where('code', 'OM')->value('id');

    DB::table('states')->where('country_id', $omanId)->delete();

    expect((new StateProfile)->apply('ar', 'OM'))->toBe(11)
        ->and(DB::table('states')->where('country_id', $omanId)->where('name', 'مسقط')->exists())->toBeTrue()
        ->and((new StateProfile)->apply('ar', 'OM'))->toBe(0);
});

it('translates country and currency names from the ICU data', function (): void {
    $before = DB::table('countries')->where('code', 'OM')->value('name');

    DB::table('countries')->where('code', 'OM')->update(['name' => 'Oman']);

    (new SeedDataLocalizer)->localize('ar');

    expect(DB::table('countries')->where('code', 'OM')->value('name'))->toBe('عُمان');

    DB::table('countries')->where('code', 'OM')->update(['name' => $before]);
});
