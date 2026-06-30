<?php

namespace App\Http\Controllers;

use App\Support\Media\PrivateMediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateFileServeController extends Controller
{
    public function __invoke(Request $request): StreamedResponse|Response
    {
        abort_unless(Auth::check(), 401);

        $path = $request->string('path')->toString();
        $disposition = $request->string('disposition', 'attachment')->toString();

        abort_unless(PrivateMediaUrl::isAllowedPath($path), 404);
        abort_unless(Storage::disk('private')->exists($path), 404);

        $filename = basename($path);
        $mimeType = Storage::disk('private')->mimeType($path) ?: 'application/octet-stream';

        if ($disposition === 'inline') {
            return response(Storage::disk('private')->get($path), 200)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'inline; filename="'.rawurlencode($filename).'"')
                ->header('X-Frame-Options', 'SAMEORIGIN');
        }

        return Storage::disk('private')->download($path, $filename);
    }
}
