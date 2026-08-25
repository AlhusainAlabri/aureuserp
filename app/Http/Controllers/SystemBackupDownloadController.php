<?php

namespace App\Http\Controllers;

use App\Models\Operations\SystemBackup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemBackupDownloadController extends Controller
{
    public function __invoke(Request $request, SystemBackup $backup): StreamedResponse
    {
        Gate::authorize('download', $backup);

        abort_unless($backup->existsOnDisk(), 404);

        return Storage::disk($backup->disk)->download($backup->path, $backup->name.'.'.pathinfo($backup->path, PATHINFO_EXTENSION));
    }
}
