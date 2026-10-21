<?php

namespace Webkul\Correspondence\Filament\Resources\CorrespondenceResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource\Pages\Concerns\PersistsCorrespondenceUploads;
use Webkul\Correspondence\Models\Correspondence;

class CreateCorrespondence extends CreateRecord
{
    use PersistsCorrespondenceUploads;

    protected static string $resource = CorrespondenceResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->extractPendingUploads($data);
    }

    protected function afterCreate(): void
    {
        $this->persistPendingUploads();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $parentId = request()->integer('reply_to');

        if (! $parentId) {
            return $data;
        }

        $parent = Correspondence::query()->find($parentId);

        if (! $parent) {
            return $data;
        }

        return [
            ...$data,
            'parent_id'          => $parent->id,
            'direction'          => $parent->isIncoming() ? 'outgoing' : 'incoming',
            'type'               => $parent->type === 'external' ? 'external' : 'internal',
            'priority'           => $parent->priority,
            'subject'            => __('correspondence::correspondence.reply_subject', ['subject' => $parent->subject]),
            'from_department_id' => $parent->to_department_id,
            'to_department_id'   => $parent->from_department_id,
            'to_user_id'         => $parent->creator_id,
            'sender_entity'      => $parent->sender_entity,
            'project_id'         => $parent->project_id,
            'meeting_id'         => $parent->meeting_id,
        ];
    }
}
