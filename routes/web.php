<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Webkul\Employee\Models\EmployeeDocument;

if (! request()->getRequestUri() == '/login') {
    Route::redirect('/login', '/admin/login')
        ->name('login');
}

Route::get('/employees/documents/{document}/download', function (EmployeeDocument $document) {
    return Storage::disk('private')->download($document->file_path, $document->document_name);
})->name('employees.documents.download')->middleware(['auth']);
