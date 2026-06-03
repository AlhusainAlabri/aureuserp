<?php

namespace Webkul\DocumentArchive\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\DocumentArchive\Models\DocShareLink;
use Webkul\DocumentArchive\Services\DocumentAccessService;

class DocumentShareController extends Controller
{
    public function __invoke(Request $request, string $token, DocumentAccessService $access): StreamedResponse|View|RedirectResponse|Response
    {
        $link = DocShareLink::query()->where('token', $token)->first();

        if (! $link || ! $link->isValid()) {
            return response()->view('document-archive::shared.expired', [], 410);
        }

        $file = $link->file;

        if (! $file) {
            return response()->view('document-archive::shared.expired', [], 410);
        }

        if ($access->requiresPassword($file) && ! $access->isShareUnlocked($token, $file)) {
            if ($request->isMethod('post')) {
                $request->validate([
                    'password' => ['required', 'string'],
                ]);

                if (! $access->attemptShareUnlock($token, $file, $request->string('password')->toString())) {
                    return back()->withErrors([
                        'password' => __('document-archive::document-archive.password.invalid'),
                    ]);
                }

                return redirect()->route('document-archive.share', ['token' => $token]);
            }

            return view('document-archive::shared.password', [
                'file'      => $file,
                'actionUrl' => route('document-archive.share', ['token' => $token]),
            ]);
        }

        $link->markAsViewed();
        $file->incrementViewCount();

        $file->activities()->create([
            'user_id'    => $link->shared_by,
            'action'     => 'shared',
            'metadata'   => ['token' => $token],
            'ip_address' => $request->ip(),
        ]);

        $disk = Storage::disk(config('document-archive.storage_disk', 'private'));

        if (! $disk->exists($file->file_path)) {
            return response()->view('document-archive::shared.missing-file', [
                'file' => $file,
            ], 404);
        }

        return $disk->response($file->file_path, $file->original_filename, [
            'Content-Type' => $file->mime_type,
        ]);
    }
}
