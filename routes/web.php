<?php

use App\Http\Controllers\Inventory\InventoryReportDownloadController;
use App\Http\Controllers\SetUserLocaleController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Webkul\Employee\Models\EmployeeDocument;

if (! request()->getRequestUri() == '/login') {
    Route::redirect('/login', '/admin/login')
        ->name('login');
}

Route::get('/inventory/reports/download', InventoryReportDownloadController::class)
    ->name('inventory.reports.download')
    ->middleware(['auth', 'signed']);

Route::get('/employees/documents/{document}/download', function (EmployeeDocument $document) {
    return Storage::disk('local')->download($document->file_path, $document->document_name);
})->name('employees.documents.download')->middleware(['auth']);

Route::get('/employees/documents/{document}/preview', function (EmployeeDocument $document) {
    abort_if(! Storage::disk('local')->exists($document->file_path), 404);

    $contents = Storage::disk('local')->get($document->file_path);
    $mimeType = Storage::disk('local')->mimeType($document->file_path);

    return response($contents, 200)
        ->header('Content-Type', $mimeType)
        ->header('Content-Disposition', 'inline; filename="'.rawurlencode($document->document_name).'"')
        ->header('X-Frame-Options', 'SAMEORIGIN');
})->name('employees.documents.preview')->middleware(['auth']);

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::redirect('/admin/my-requests', '/admin/purchase/orders/my-requests');
    Route::redirect('/admin/internal-requests', '/admin/purchase/orders/internal-requests');
});

Route::middleware(['web'])->group(function (): void {
    Route::get('/locale/{locale}', SetUserLocaleController::class)
        ->name('locale.switch');
});
