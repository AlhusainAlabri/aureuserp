<?php

use Illuminate\Support\Facades\Schema;
use Webkul\Partner\PartnerServiceProvider;
use Webkul\Security\Models\User;

it('keeps the account columns on partners in the core partners plugin', function (): void {
    $migration = '2026_10_25_000001_add_account_columns_to_partners_partners_table';

    $registered = (new ReflectionClass(PartnerServiceProvider::class))->getFileName();

    expect(file_get_contents($registered))->toContain($migration)
        ->and(file_exists(base_path("plugins/webkul/partners/database/migrations/{$migration}.php")))->toBeTrue();

    foreach (['is_active', 'password', 'remember_token', 'email_verified_at'] as $column) {
        expect(Schema::hasColumn('partners_partners', $column))->toBeTrue();
    }
});

it('mirrors a new user into a partner row', function (): void {
    $user = User::factory()->create(['is_active' => true]);

    expect($user->fresh()->partner)->not->toBeNull()
        ->and($user->fresh()->partner->is_active)->toBeTrue();
});
