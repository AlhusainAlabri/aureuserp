<?php

use App\Filament\Assets\Resources\AssetBorrowingResource\Pages\ViewAssetBorrowing;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Webkul\Assets\Models\Asset;
use Webkul\Assets\Models\AssetBorrowing;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    if (! Schema::hasTable('assets')) {
        Artisan::call('assets:install', ['--no-interaction' => true]);
    }
});

it('loads the asset borrowing view page without index route error', function (): void {
    $user = User::withoutEvents(fn (): User => User::factory()->create());

    foreach (['view_assets_asset_borrowing', 'view_any_assets_asset_borrowing'] as $permission) {
        Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $user->givePermissionTo($permission);
    }

    $this->actingAs($user);

    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $asset = Asset::factory()->create();

    $borrowing = AssetBorrowing::query()->create([
        'asset_id'    => $asset->id,
        'employee_id' => $employee->id,
        'due_at'      => now()->addDays(5),
    ]);

    Livewire::test(ViewAssetBorrowing::class, ['record' => $borrowing->id])
        ->assertSuccessful();
});
