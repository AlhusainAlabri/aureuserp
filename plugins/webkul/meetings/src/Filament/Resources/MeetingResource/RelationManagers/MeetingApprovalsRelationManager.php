<?php

namespace Webkul\Meetings\Filament\Resources\MeetingResource\RelationManagers;

use Filament\Tables\Table;
use Webkul\Meetings\Filament\Resources\MeetingResource\RelationManagers\Concerns\HasMeetingRelationCountBadge;
use Wezlo\FilamentApproval\RelationManagers\ApprovalsRelationManager;

class MeetingApprovalsRelationManager extends ApprovalsRelationManager
{
    use HasMeetingRelationCountBadge;

    public static function getTitle($ownerRecord = null, ?string $pageClass = null): string
    {
        return __('meetings::meetings.relations.approvals');
    }

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->emptyStateHeading(__('meetings::meetings.empty.no_approval_log'))
            ->emptyStateDescription(__('meetings::meetings.empty.no_approval_log_description'));
    }
}
