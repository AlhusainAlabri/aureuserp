<?php

use Illuminate\Support\Facades\Route;
use Webkul\DocumentArchive\Http\Controllers\DocumentDownloadController;
use Webkul\DocumentArchive\Http\Controllers\DocumentPreviewController;
use Webkul\DocumentArchive\Http\Controllers\DocumentShareController;

Route::middleware(['web'])->group(function (): void {
    Route::match(['get', 'post'], '/share/{token}', DocumentShareController::class)
        ->name('document-archive.share');

    Route::middleware('auth')->group(function (): void {
        Route::match(['get', 'post'], '/admin/documents/preview/{file}', DocumentPreviewController::class)
            ->name('document-archive.preview');

        Route::match(['get', 'post'], '/admin/documents/download/{file}', DocumentDownloadController::class)
            ->name('document-archive.download');
    });
});
