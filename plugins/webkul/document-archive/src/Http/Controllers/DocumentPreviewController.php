<?php

namespace Webkul\DocumentArchive\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Webkul\DocumentArchive\Models\DocFile;

class DocumentPreviewController extends Controller
{
    public function __invoke(Request $request, DocFile $file)
    {
        abort_unless(Auth::check(), 401);
        abort_unless($file->canBeAccessedBy(Auth::user()), 403);

        $file->incrementViewCount();

        $file->activities()->create([
            'user_id'    => Auth::id(),
            'action'     => 'viewed',
            'ip_address' => $request->ip(),
        ]);

        $path = storage_path('app/'.$file->file_path);
        abort_unless(file_exists($path), 404);

        return response()->file($path, [
            'Content-Type' => $file->mime_type,
        ]);
    }
}
