<?php

namespace Webkul\DocumentArchive\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webkul\DocumentArchive\Models\DocShareLink;

class DocumentShareController extends Controller
{
    public function __invoke(Request $request, string $token)
    {
        $link = DocShareLink::query()->where('token', $token)->first();

        if (! $link || ! $link->isValid()) {
            return response()->view('document-archive::shared.expired', [], 410);
        }

        $file = $link->file;

        if (! $file) {
            return response()->view('document-archive::shared.expired', [], 410);
        }

        $link->markAsViewed();
        $file->incrementViewCount();

        $file->activities()->create([
            'user_id'    => $link->shared_by,
            'action'     => 'shared',
            'metadata'   => ['token' => $token],
            'ip_address' => $request->ip(),
        ]);

        $path = storage_path('app/'.$file->file_path);

        if (! file_exists($path)) {
            return response()->view('document-archive::shared.expired', [], 404);
        }

        return response()->file($path, [
            'Content-Type' => $file->mime_type,
        ]);
    }
}
