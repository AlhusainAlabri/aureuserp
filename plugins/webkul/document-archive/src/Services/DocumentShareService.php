<?php

namespace Webkul\DocumentArchive\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Webkul\DocumentArchive\Mail\DocumentShareMail;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Models\DocShareLink;

class DocumentShareService
{
    /**
     * @param  array{shared_with_email?: string|null, view_once?: bool, expires_at?: string|null}  $data
     */
    public function createLink(DocFile $file, array $data): DocShareLink
    {
        $link = DocShareLink::query()->create([
            'file_id'           => $file->id,
            'shared_by'         => Auth::id(),
            'shared_with_email' => $data['shared_with_email'] ?? null,
            'view_once'         => (bool) ($data['view_once'] ?? false),
            'expires_at'        => $data['expires_at'] ?? null,
            'is_active'         => true,
        ]);

        $file->activities()->create([
            'user_id'  => Auth::id(),
            'action'   => 'shared',
            'metadata' => [
                'share_link_id' => $link->id,
                'view_once'     => $link->view_once,
            ],
        ]);

        if (filled($link->shared_with_email)) {
            Mail::to($link->shared_with_email)->queue(new DocumentShareMail($file, $link));
        }

        return $link;
    }
}
