<?php

namespace Webkul\DocumentArchive\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Services\DocumentAccessService;
use Webkul\DocumentArchive\Services\DocumentStorageService;

class DocumentPreviewController extends Controller
{
    public function __invoke(Request $request, DocFile $file, DocumentAccessService $access, DocumentStorageService $storage): StreamedResponse|View|RedirectResponse|Response
    {
        abort_unless(Auth::check(), 401);
        abort_unless($access->canViewFile(Auth::user(), $file), 403);

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

                return redirect()->route('document-archive.preview', ['file' => $file->id]);
            }

            return view('document-archive::shared.password', [
                'file'      => $file,
                'actionUrl' => route('document-archive.preview', ['file' => $file->id]),
            ]);
        }

        if (! $request->boolean('embed')) {
            $access->recordView($file, $request);
        }

        if (! $storage->fileExists($file)) {
            return response()->view('document-archive::shared.missing-file', [
                'file' => $file,
            ], 404);
        }

        return $this->streamFile($file);
    }

    protected function streamFile(DocFile $file): StreamedResponse
    {
        $disk = Storage::disk(config('document-archive.storage_disk', 'private'));

        return $disk->response($file->file_path, $file->original_filename, [
            'Content-Type'        => $file->mime_type,
            'Content-Disposition' => 'inline',
        ]);
    }
}
