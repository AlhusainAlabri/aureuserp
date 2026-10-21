<?php

namespace Webkul\Correspondence\Filament\Resources\CorrespondenceResource\Pages\Concerns;

use Webkul\Correspondence\Models\Correspondence;
use Webkul\Correspondence\Services\CorrespondenceAttachmentService;

trait PersistsCorrespondenceUploads
{
    /**
     * @var array<int, string>
     */
    protected array $pendingAttachmentPaths = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractPendingUploads(array $data): array
    {
        $this->pendingAttachmentPaths = CorrespondenceAttachmentService::normalizePaths($data['uploads'] ?? []);
        unset($data['uploads']);

        return $data;
    }

    protected function persistPendingUploads(): void
    {
        $record = $this->record;

        if (! $record instanceof Correspondence) {
            return;
        }

        CorrespondenceAttachmentService::storeFromPaths($record, $this->pendingAttachmentPaths);
        $this->pendingAttachmentPaths = [];
    }
}
