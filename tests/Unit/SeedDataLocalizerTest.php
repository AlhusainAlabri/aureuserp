<?php

use App\Services\Install\SeedDataLocalizer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    Schema::dropIfExists('qa_seed_units');
    Schema::create('qa_seed_units', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('full_name')->nullable();
    });

    DB::table('qa_seed_units')->insert([
        ['name' => 'Units', 'full_name' => null],
        ['name' => 'Cartons', 'full_name' => null],
        ['name' => 'وحدات مخصصة', 'full_name' => null],
    ]);
});

afterEach(fn () => Schema::dropIfExists('qa_seed_units'));

it('translates only the rows that still hold the seeded English value', function (): void {
    $updated = (new SeedDataLocalizer)->localize('ar', ['qa_seed_units.name' => ['Units' => 'وحدات', 'Missing' => 'غير موجود']]);

    expect($updated)->toBe(['qa_seed_units.name' => 1])
        ->and(DB::table('qa_seed_units')->pluck('name')->all())->toBe(['وحدات', 'Cartons', 'وحدات مخصصة']);
});

it('is safe to run twice and keeps customer edits', function (): void {
    $dictionary = ['qa_seed_units.name' => ['Units' => 'وحدات']];

    (new SeedDataLocalizer)->localize('ar', $dictionary);
    DB::table('qa_seed_units')->where('name', 'وحدات')->update(['name' => 'اسم عدّله العميل']);

    $second = (new SeedDataLocalizer)->localize('ar', $dictionary);

    expect($second)->toBe([])
        ->and(DB::table('qa_seed_units')->where('name', 'اسم عدّله العميل')->exists())->toBeTrue();
});

it('ignores tables and columns that do not exist', function (): void {
    expect((new SeedDataLocalizer)->localize('ar', ['no_such_table.name' => ['A' => 'ب'], 'qa_seed_units.nope' => ['A' => 'ب']]))->toBe([]);
});

it('ships an Arabic dictionary whose keys point at real seeded columns', function (): void {
    $dictionary = require lang_path('ar/seed-data.php');

    foreach ($dictionary as $target => $translations) {
        expect($target)->toMatch('/^[a-z_]+\.[a-z_]+$/')
            ->and($translations)->not->toBeEmpty();
    }
});

it('registers the localize command', function (): void {
    $this->artisan('erp:localize-data en')->assertSuccessful();
});
