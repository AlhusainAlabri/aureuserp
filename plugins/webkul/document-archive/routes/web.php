<?php

use Illuminate\Support\Facades\Route;
use Webkul\DocumentArchive\Http\Controllers\DocumentPreviewController;
use Webkul\DocumentArchive\Http\Controllers\DocumentShareController;

Route::middleware(['web'])->group(function (): void {
    Route::get('/share/{token}', DocumentShareController::class)
        ->name('document-archive.share');

    Route::middleware('auth')->group(function (): void {
        Route::get('/admin/documents/preview/{file}', DocumentPreviewController::class)
            ->name('document-archive.preview');
    });
});
