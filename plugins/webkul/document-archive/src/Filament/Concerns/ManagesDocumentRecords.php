<?php

namespace Webkul\DocumentArchive\Filament\Concerns;

use Illuminate\Support\Facades\Auth;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Services\DocumentAccessService;
use Webkul\DocumentArchive\Services\DocumentStorageService;
use Webkul\DocumentArchive\Services\DocumentTagService;

trait ManagesDocumentRecords
{
    protected function mutateDocumentFormData(array $data): array
    {
        $accessService = app(DocumentAccessService::class);
        $tagService = app(DocumentTagService::class);

        if (array_key_exists('tag_names', $data) || ! empty($data['tags'])) {
            $data['tags'] = $accessService->normalizeTags(
                $tagService->resolveTagsFromInput($data),
            );
        }

        unset($data['upload'], $data['password'], $data['remove_password'], $data['tag_names'], $data['tag_colors']);

        return $data;
    }

    protected function mutateDocumentFormDataBeforeFill(array $data, DocFile $record): array
    {
        $data['tag_names'] = collect($record->getTagsWithColors())->pluck('name')->all();

        return $data;
    }

    protected function handleDocumentPassword(mixed $record, array $rawState): void
    {
        $accessService = app(DocumentAccessService::class);

        if (! empty($rawState['remove_password'])) {
            $accessService->setPassword($record, null, true);

            return;
        }

        if (filled($rawState['password'] ?? null)) {
            $accessService->setPassword($record, $rawState['password']);
        }
    }

    protected function handleDocumentUpload(mixed $record, array $rawState, bool $isCreate): void
    {
        $upload = $rawState['upload'] ?? null;

        if ($upload === null || $upload === []) {
            return;
        }

        $storage = app(DocumentStorageService::class);

        if ($isCreate) {
            $storage->attachToFile($record, $upload);

            return;
        }

        $storage->replaceFile($record, $upload);
    }

    protected function defaultCompanyId(): ?int
    {
        return Auth::user()?->default_company_id;
    }
}
