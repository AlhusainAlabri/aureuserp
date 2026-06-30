<?php

use App\Support\Media\PrivateMediaUrl;
use Illuminate\Support\Facades\Storage;
use Webkul\Security\Models\User;

it('builds a signed download url for purchase documents on the local private disk', function (): void {
    Storage::fake('private');

    $path = 'purchases/documents/2026/PO-001/quote.pdf';
    Storage::disk('private')->put($path, 'pdf-content');

    $url = PrivateMediaUrl::downloadUrl($path);

    expect($url)->not->toBeNull()
        ->and($url)->toContain('private-files/serve')
        ->and($url)->toContain('signature=');

    $this->actingAs(User::factory()->create())
        ->get($url)
        ->assertSuccessful();
});

it('builds a signed inline preview url for sales documents on the local private disk', function (): void {
    Storage::fake('private');

    $path = 'sales/2026/SO-001/invoice.pdf';
    Storage::disk('private')->put($path, 'pdf-content');

    $url = PrivateMediaUrl::inlineUrl($path);

    expect($url)->not->toBeNull()
        ->and($url)->toContain('disposition=inline')
        ->and($url)->toContain('signature=');

    $response = $this->actingAs(User::factory()->create())->get($url);

    $response->assertSuccessful()
        ->assertHeader('Content-Disposition');
});

it('builds a signed download url for invoice documents on the local private disk', function (): void {
    Storage::fake('private');

    $path = 'invoices/documents/2026/RI-2026-6/test.pdf';
    Storage::disk('private')->put($path, 'pdf-content');

    $url = PrivateMediaUrl::downloadUrl($path);

    expect($url)->not->toBeNull()
        ->and($url)->toContain('private-files/serve')
        ->and($url)->toContain('signature=');

    $this->actingAs(User::factory()->create())
        ->get($url)
        ->assertSuccessful();
});

it('builds a signed download url for vendor bill documents on the local private disk', function (): void {
    Storage::fake('private');

    $path = 'invoices/vendor-documents/2026/BILL-001/receipt.pdf';
    Storage::disk('private')->put($path, 'pdf-content');

    $url = PrivateMediaUrl::downloadUrl($path);

    expect($url)->not->toBeNull()
        ->and($url)->toContain('private-files/serve')
        ->and($url)->toContain('signature=');

    $this->actingAs(User::factory()->create())
        ->get($url)
        ->assertSuccessful();
});

it('rejects paths outside allowed private prefixes', function (): void {
    expect(PrivateMediaUrl::isAllowedPath('../../../etc/passwd'))->toBeFalse()
        ->and(PrivateMediaUrl::isAllowedPath('meetings/secret.pdf'))->toBeFalse()
        ->and(PrivateMediaUrl::downloadUrl('meetings/secret.pdf'))->toBeNull();
});
