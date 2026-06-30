<?php

use App\Support\Media\PrivateUploadMetadata;
use Illuminate\Support\Facades\Storage;

it('enriches file metadata from the private disk', function (): void {
    Storage::fake('private');

    $path = 'purchases/documents/2026/quote.pdf';
    Storage::disk('private')->put($path, 'pdf-content');

    $result = PrivateUploadMetadata::enrich([
        'file_path' => $path,
    ]);

    expect($result['file_name'])->toBe('quote.pdf')
        ->and($result['file_size'])->toBe(11)
        ->and($result['mime_type'])->toBe('application/pdf')
        ->and($result['title'])->toBe('quote.pdf');
});

it('does not read metadata from temp paths before the file is stored', function (): void {
    Storage::fake('private');

    $result = PrivateUploadMetadata::enrich([
        'file_path' => '/var/folders/tmp/php8itpee96bgkp9V2zFe3',
        'file_name' => 'quote.pdf',
        'file_size' => 0,
        'mime_type' => 'application/octet-stream',
    ]);

    expect($result['file_name'])->toBe('quote.pdf')
        ->and($result['file_size'])->toBe(0)
        ->and($result['mime_type'])->toBe('application/octet-stream');
});

it('preserves a custom title when enriching metadata', function (): void {
    Storage::fake('private');

    $path = 'purchases/documents/2026/quote.pdf';
    Storage::disk('private')->put($path, 'pdf-content');

    $result = PrivateUploadMetadata::enrich([
        'file_path' => $path,
        'title'     => 'عرض سعر',
    ]);

    expect($result['title'])->toBe('عرض سعر');
});
