<?php

use App\Support\Media\PublicMediaUrl;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Support\Facades\Storage;
use Webkul\Partner\Models\Partner;

it('resolves avatar urls from the public disk', function (): void {
    Storage::fake('public');
    Storage::fake('local');

    Storage::disk('public')->put('company-logos/logo.png', 'logo-bytes');

    expect(PublicMediaUrl::url('company-logos/logo.png'))
        ->toBe(Storage::disk('public')->url('company-logos/logo.png'));
});

it('falls back to a signed url when the file is still on the local disk', function (): void {
    Storage::fake('public');
    Storage::fake('local');

    Storage::disk('local')->put('company-logos/logo.png', 'logo-bytes');

    $url = PublicMediaUrl::url('company-logos/logo.png');

    expect($url)->not->toBeNull()
        ->and($url)->toContain('company-logos/logo.png')
        ->and($url)->toContain('expiration=');
});

it('migrates public media directories from the local disk to the public disk', function (): void {
    Storage::fake('public');
    Storage::fake('local');

    Storage::disk('local')->put('company-logos/logo.png', 'logo-bytes');
    Storage::disk('local')->put('partners/avatar/user.png', 'avatar-bytes');

    $result = PublicMediaUrl::migrateFromLocalDisk();

    expect($result)->toBe([
        'migrated' => 2,
        'skipped'  => 0,
    ]);

    Storage::disk('public')->assertExists('company-logos/logo.png');
    Storage::disk('public')->assertExists('partners/avatar/user.png');
});

it('exposes partner avatar urls through the public media resolver', function (): void {
    Storage::fake('public');
    Storage::fake('local');

    Storage::disk('public')->put('company-logos/logo.png', 'logo-bytes');

    $partner = Partner::factory()->create([
        'avatar' => 'company-logos/logo.png',
    ]);

    expect($partner->avatar_url)
        ->toBe(Storage::disk('public')->url('company-logos/logo.png'));
});

it('uses the public disk for public file uploads', function (): void {
    $upload = FileUpload::make('avatar')
        ->visibility('public');

    expect($upload->getDiskName())->toBe('public');
});

it('uses the public disk for avatar image columns', function (): void {
    $column = ImageColumn::make('partner.avatar');

    expect($column->getDiskName())->toBe('public');
});
