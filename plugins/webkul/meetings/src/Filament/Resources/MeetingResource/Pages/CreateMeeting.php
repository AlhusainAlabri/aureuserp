<?php

namespace Webkul\Meetings\Filament\Resources\MeetingResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Webkul\Meetings\Filament\Resources\MeetingResource;

class CreateMeeting extends CreateRecord
{
    protected static string $resource = MeetingResource::class;

    public function getTitle(): string
    {
        return __('meetings::meetings.pages.create_title');
    }
}
