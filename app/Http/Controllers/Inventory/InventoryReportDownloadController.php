<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryReportDownloadController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        abort_unless(Auth::check(), 401);
        abort_unless(Gate::allows('page_inventory_movement_report'), 403);

        $path = $request->string('path')->toString();

        abort_unless($this->isAllowedPath($path), 404);
        abort_unless(Storage::disk('private')->exists($path), 404);

        $filename = basename($path);

        return Storage::disk('private')->download($path, $filename);
    }

    protected function isAllowedPath(string $path): bool
    {
        if (str_contains($path, '..')) {
            return false;
        }

        return (bool) preg_match('#^inventory/(pdf|csv)/\d{4}/movement-report-.+\.(pdf|csv)$#', $path);
    }
}
