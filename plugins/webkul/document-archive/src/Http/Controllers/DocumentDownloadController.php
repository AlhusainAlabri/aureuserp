<?php

namespace Webkul\DocumentArchive\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Services\DocumentAccessService;
use Webkul\DocumentArchive\Services\DocumentStorageService;

class DocumentDownloadController extends Controller
{
    public function __invoke(Request $request, DocFile $file, DocumentAccessService $access, DocumentStorageService $storage): BinaryFileResponse|StreamedResponse|View|RedirectResponse|Response
    {
        abort_unless(Auth::check(), 401);
        abort_unless($access->canDownloadFile(Auth::user(), $file), 403);

        if ($access->requiresPassword($file) && ! $access->isFileUnlocked($file, $request)) {
            if ($request->isMethod('post')) {
                $request->validate([
                    'password' => ['required', 'string'],
                ]);

                if (! $access->attemptUnlock($file, $request->string('password')->toString(), $request)) {
                    return back()->withErrors([
                        'password' => __('document-archive::document-archive.password.invalid'),
                    ]);
                }

                return redirect()->route('document-archive.download', ['file' => $file->id]);
            }

            return view('document-archive::shared.password', [
                'file'      => $file,
                'actionUrl' => route('document-archive.download', ['file' => $file->id]),
            ]);
        }

        $file->incrementDownloadCount();

        $file->activities()->create([
            'user_id'    => Auth::id(),
            'action'     => 'downloaded',
            'ip_address' => $request->ip(),
        ]);

        if (! $storage->fileExists($file)) {
            return response()->view('document-archive::shared.missing-file', [
                'file' => $file,
            ], 404);
        }

        $disk = Storage::disk(config('document-archive.storage_disk', 'private'));

        return $disk->download($file->file_path, $file->original_filename);
    }
}
