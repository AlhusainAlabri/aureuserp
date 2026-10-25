<?php

namespace Webkul\Attendance\Filament\Resources\AttendanceDayResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\Attendance\Enums\DayStatus;
use Webkul\Attendance\Filament\Resources\AttendanceDayResource;
use Webkul\Attendance\Models\AttendanceDay;
use Webkul\Attendance\Services\AttendanceDayCsv;

class ListAttendanceDays extends ListRecords
{
    protected static string $resource = AttendanceDayResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all'  => Tab::make(__('attendances::attendances.day.tabs.all')),
            'late' => Tab::make(__('attendances::attendances.day.tabs.late'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('late_minutes', '>', 0)),
            'absent' => Tab::make(__('attendances::attendances.day.tabs.absent'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', DayStatus::Absent->value)),
            'overtime' => Tab::make(__('attendances::attendances.day.tabs.overtime'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('overtime_minutes', '>', 0)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label(__('attendances::attendances.day.actions.export_csv'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (): StreamedResponse => $this->downloadCsv()),
            CreateAction::make()
                ->icon('heroicon-o-plus')
                ->label(__('attendances::attendances.day.actions.add_entry')),
        ];
    }

    /**
     * The rows the list currently shows (tab, filters, search and sort applied, scope included).
     *
     * @return Collection<int, AttendanceDay>
     */
    protected function exportRows(): Collection
    {
        return $this->getFilteredSortedTableQuery()->with('employee')->get();
    }

    protected function selectedMonth(): string
    {
        return AttendanceDayResource::normaliseMonth($this->getTableFilterState('month')['month'] ?? null);
    }

    protected function downloadCsv(): StreamedResponse
    {
        $csv = app(AttendanceDayCsv::class)->build($this->exportRows());

        return response()->streamDownload(
            function () use ($csv): void {
                echo $csv;
            },
            sprintf('attendance-days-%s.csv', $this->selectedMonth()),
            ['Content-Type' => 'text/csv'],
        );
    }
}
