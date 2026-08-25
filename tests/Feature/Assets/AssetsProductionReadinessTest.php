<?php

use App\Filament\Assets\Actions\RequestAssetAction;
use App\Filament\Assets\Pages\MyBorrowingRequests;
use App\Http\Middleware\SetLocale;
use App\Http\Responses\LocaleAwareLoginResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Webkul\Assets\Enums\AssetCategory;
use Webkul\Assets\Filament\Resources\AssetResource;
use Webkul\Assets\Filament\Resources\AssetResource\Pages\ViewAsset;
use Webkul\Assets\Models\Asset;
use Webkul\Assets\Models\AssetBorrowing;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    if (! Schema::hasTable('assets')) {
        Artisan::call('assets:install', ['--no-interaction' => true]);
    }
});

it('detects vehicle category from translated arabic labels', function (): void {
    app()->setLocale('ar');

    expect(AssetResource::isVehicleCategory(AssetCategory::Vehicle->value))->toBeTrue()
        ->and(AssetResource::isVehicleCategory(__('assets-extensions::categories.vehicle')))->toBeTrue()
        ->and(AssetResource::isVehicleCategory(__('assets-extensions::categories.furniture')))->toBeFalse();
});

it('shows vehicle details on the asset view infolist', function (): void {
    app()->setLocale('ar');

    $user = User::withoutEvents(fn (): User => User::factory()->create());
    Permission::findOrCreate('view_assets_asset', 'web');
    Permission::findOrCreate('view_any_assets_asset', 'web');
    $user->givePermissionTo(['view_assets_asset', 'view_any_assets_asset']);
    $this->actingAs($user);

    $asset = Asset::factory()->create([
        'category'            => AssetCategory::Vehicle->value,
        'plate_number'        => 'A 12345',
        'registration_number' => 'REG-001',
        'mileage'             => 12000,
    ]);

    Livewire::test(ViewAsset::class, ['record' => $asset->id])
        ->assertSee(__('assets-extensions::fields.plate_number'))
        ->assertSee('A 12345')
        ->assertSee('REG-001');
});

it('shows the request asset header action for employees on my requests page', function (): void {
    app()->setLocale('ar');

    $user = User::withoutEvents(fn (): User => User::factory()->create());
    Permission::findOrCreate('page_my_borrowing_requests', 'web');
    Permission::findOrCreate('request_borrow_assets_asset', 'web');
    $user->givePermissionTo(['page_my_borrowing_requests', 'request_borrow_assets_asset']);

    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $employee->update(['user_id' => $user->id]);

    $this->actingAs($user->fresh());

    Livewire::test(MyBorrowingRequests::class)
        ->assertSuccessful()
        ->assertSee(__('assets-extensions::actions.request_asset'));
});

it('registers the request asset action for linked employees', function (): void {
    $user = User::withoutEvents(fn (): User => User::factory()->create());
    Permission::findOrCreate('request_borrow_assets_asset', 'web');
    $user->givePermissionTo('request_borrow_assets_asset');

    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $employee->update(['user_id' => $user->id]);

    $this->actingAs($user->fresh());

    $action = RequestAssetAction::make();

    expect($action->isVisible())->toBeTrue();
});

it('redirects to admin with locale after login when guest session locale is arabic', function (): void {
    config()->set('app.supported_locales', [
        'en' => ['label' => 'English', 'native' => 'English', 'flag' => 'en', 'rtl' => false],
        'ar' => ['label' => 'Arabic', 'native' => 'العربية', 'flag' => 'ar', 'rtl' => true],
    ]);

    $session = app('session.store');
    $session->start();
    $session->put('locale', 'ar');

    $user = User::factory()->create(['language' => 'en']);
    $request = Request::create('/admin/login', 'POST');
    $request->setLaravelSession($session);
    $request->setUserResolver(fn () => $user);

    $response = app(LocaleAwareLoginResponse::class)->toResponse($request);

    expect($response->getTargetUrl())->toContain('lang=ar')
        ->and($user->fresh()->language)->toBe('ar')
        ->and($session->has('locale'))->toBeFalse();
});

it('syncs guest session locale to the authenticated user profile', function (): void {
    config()->set('app.supported_locales', [
        'en' => ['label' => 'English', 'native' => 'English', 'flag' => 'en', 'rtl' => false],
        'ar' => ['label' => 'Arabic', 'native' => 'العربية', 'flag' => 'ar', 'rtl' => true],
    ]);

    Session::put('locale', 'ar');

    $user = User::factory()->create(['language' => 'en']);

    $request = Request::create('/admin');
    $request->setUserResolver(fn () => $user);
    $request->setLaravelSession(app('session.store'));

    app(SetLocale::class)->handle(
        $request,
        fn ($request) => response('ok'),
    );

    expect($user->fresh()->language)->toBe('ar')
        ->and(app()->getLocale())->toBe('ar');
});

it('uses signature field on asset return action', function (): void {
    $user = User::withoutEvents(fn (): User => User::factory()->create());
    Permission::findOrCreate('view_assets_asset', 'web');
    Permission::findOrCreate('view_any_assets_asset', 'web');
    Permission::findOrCreate('return_asset_assets_asset', 'web');
    $user->givePermissionTo(['view_assets_asset', 'view_any_assets_asset', 'return_asset_assets_asset']);
    $this->actingAs($user);

    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $asset = Asset::factory()->borrowed()->create();

    AssetBorrowing::query()->create([
        'asset_id'    => $asset->id,
        'employee_id' => $employee->id,
        'due_at'      => now()->addDays(3),
    ]);

    Livewire::test(ViewAsset::class, ['record' => $asset->id])
        ->callAction('return', data: [
            'signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        ])
        ->assertNotified();
});
