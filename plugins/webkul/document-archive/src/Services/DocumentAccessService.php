<?php

namespace Webkul\DocumentArchive\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Models\DocFolder;
use Webkul\Security\Models\User;

class DocumentAccessService
{
    public function sessionUnlockMinutes(): int
    {
        return (int) config('document-archive.session_unlock_minutes', 30);
    }

    public function fileSessionKey(DocFile $file): string
    {
        return 'doc_unlock_file_'.$file->id;
    }

    public function folderSessionKey(DocFolder $folder): string
    {
        return 'doc_unlock_folder_'.$folder->id;
    }

    public function shareSessionKey(string $token): string
    {
        return 'doc_share_unlock_'.$token;
    }

    public function isFileUnlocked(DocFile $file, ?Request $request = null): bool
    {
        $request ??= request();

        if (! $request->hasSession()) {
            return false;
        }

        if ($request->session()->has($this->fileSessionKey($file))) {
            return true;
        }

        if ($file->folder && $request->session()->has($this->folderSessionKey($file->folder))) {
            return true;
        }

        return false;
    }

    public function isShareUnlocked(string $token, DocFile $file): bool
    {
        if (request()->session()->has($this->shareSessionKey($token))) {
            return true;
        }

        return $this->isFileUnlocked($file);
    }

    public function requiresPassword(DocFile $file): bool
    {
        if ($file->hasPassword()) {
            return true;
        }

        return $file->folder?->hasPassword() ?? false;
    }

    public function attemptUnlock(DocFile $file, string $password, ?Request $request = null): bool
    {
        $request ??= request();

        if ($file->hasPassword() && ! $file->checkPassword($password)) {
            return false;
        }

        if ($file->folder?->hasPassword() && ! $file->folder->checkPassword($password)) {
            return false;
        }

        $expiresAt = now()->addMinutes($this->sessionUnlockMinutes());

        if ($file->hasPassword()) {
            $request->session()->put($this->fileSessionKey($file), $expiresAt->timestamp);
        }

        if ($file->folder?->hasPassword()) {
            $request->session()->put($this->folderSessionKey($file->folder), $expiresAt->timestamp);
        }

        return true;
    }

    public function attemptShareUnlock(string $token, DocFile $file, string $password): bool
    {
        if (! $this->attemptUnlock($file, $password)) {
            return false;
        }

        request()->session()->put(
            $this->shareSessionKey($token),
            now()->addMinutes($this->sessionUnlockMinutes())->timestamp,
        );

        return true;
    }

    public function canViewFile(?User $user, DocFile $file): bool
    {
        if (! $user) {
            return false;
        }

        return $file->canBeAccessedBy($user);
    }

    public function canDownloadFile(?User $user, DocFile $file): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->can('download_document_archive_doc::file')) {
            return $file->canBeAccessedBy($user);
        }

        return $file->canBeAccessedBy($user);
    }

    public function recordView(DocFile $file, ?Request $request = null): void
    {
        $request ??= request();

        $file->incrementViewCount();

        $file->activities()->create([
            'user_id'    => Auth::id(),
            'action'     => 'viewed',
            'ip_address' => $request->ip(),
        ]);
    }

    public function applyAccessibleFilesScope(Builder $query, ?User $user = null): Builder
    {
        $user ??= Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->can('view_any_document_archive_doc::file')) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($user): void {
            $builder->where('creator_id', $user->id)
                ->orWhere(function (Builder $privateQuery) use ($user): void {
                    $privateQuery->where('is_private', false)
                        ->whereHas('folder', fn (Builder $folderQuery) => $folderQuery->where(function (Builder $access) use ($user): void {
                            $access->where('is_private', false)
                                ->orWhere('creator_id', $user->id)
                                ->orWhereHas('permissions', function (Builder $permQuery) use ($user): void {
                                    $permQuery->where(function (Builder $q) use ($user): void {
                                        $q->where('type', 'user')->where('user_id', $user->id);
                                    })->orWhere(function (Builder $q) use ($user): void {
                                        $q->where('type', 'role')->whereIn('role_name', $user->roles->pluck('name')->all());
                                    })->whereIn('permission', ['view', 'upload', 'manage']);
                                });
                        }));
                });
        });
    }

    public function setPassword(DocFile|DocFolder $record, ?string $password, bool $removePassword = false): void
    {
        if ($removePassword) {
            $record->forceFill(['password_hash' => null])->save();

            return;
        }

        if (filled($password)) {
            $record->forceFill(['password_hash' => Hash::make($password)])->save();
        }
    }

    /**
     * @param  array<int, array{name?: string, color?: string|null}|string>  $tags
     * @return array<int, array{name: string, color: string|null}>
     */
    public function normalizeTags(array $tags): array
    {
        return collect($tags)
            ->map(function (array|string $tag): ?array {
                if (is_string($tag)) {
                    return ['name' => $tag, 'color' => null];
                }

                $name = trim((string) ($tag['name'] ?? ''));

                if ($name === '') {
                    return null;
                }

                return [
                    'name'  => $name,
                    'color' => $tag['color'] ?? null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
