<?php

namespace Webkul\Meetings\Filament\Resources\MeetingResource\RelationManagers\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasMeetingRelationCountBadge
{
    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->{static::$relationship}()->count();

        return $count > 0 ? (string) $count : null;
    }
}
