<?php

namespace Webkul\Attendance\Filament\Resources\OvertimeRequestResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Attendance\Enums\OvertimeStatus;
use Webkul\Attendance\Filament\Resources\OvertimeRequestResource;

class ListOvertimeRequests extends ListRecords
{
    protected static string $resource = OvertimeRequestResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $tabs = [];

        foreach ([OvertimeStatus::Pending, OvertimeStatus::Approved, OvertimeStatus::Rejected] as $status) {
            $tabs[$status->value] = Tab::make($status->getLabel())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', $status->value));
        }

        $tabs['all'] = Tab::make(__('attendances::attendances.overtime.tabs.all'));

        return $tabs;
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return OvertimeStatus::Pending->value;
    }
}
