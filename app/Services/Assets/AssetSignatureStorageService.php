<?php

namespace App\Services\Assets;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AssetSignatureStorageService
{
    public function storeBorrowSignature(int $borrowingId, string $dataUrl): string
    {
        return $this->store($borrowingId, 'borrow', $dataUrl);
    }

    public function storeReturnSignature(int $borrowingId, string $dataUrl): string
    {
        return $this->store($borrowingId, 'return', $dataUrl);
    }

    public function temporaryUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (! Storage::disk('private')->exists($path)) {
            return null;
        }

        return Storage::disk('private')->temporaryUrl($path, now()->addMinutes(60));
    }

    protected function store(int $borrowingId, string $type, string $dataUrl): string
    {
        $binary = $this->decodeDataUrl($dataUrl);
        $year = now()->year;
        $filename = sprintf('%s-%s.png', $type, Str::uuid());
        $path = "assets/signatures/{$year}/{$borrowingId}/{$filename}";

        Storage::disk('private')->put($path, $binary);

        return $path;
    }

    protected function decodeDataUrl(string $dataUrl): string
    {
        if (! str_contains($dataUrl, ',')) {
            throw new \InvalidArgumentException(__('assets-extensions::signatures.invalid_data'));
        }

        $encoded = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $decoded = base64_decode($encoded, true);

        if ($decoded === false) {
            throw new \InvalidArgumentException(__('assets-extensions::signatures.invalid_data'));
        }

        return $decoded;
    }
}
