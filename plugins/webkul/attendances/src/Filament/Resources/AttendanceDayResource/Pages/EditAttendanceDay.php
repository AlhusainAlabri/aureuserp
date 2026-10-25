<?php

namespace Webkul\Attendance\Filament\Resources\AttendanceDayResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Webkul\Attendance\Filament\Resources\AttendanceDayResource;
use Webkul\Attendance\Models\AttendanceDay;
use Webkul\Attendance\Services\ManualDayEntry;

class EditAttendanceDay extends EditRecord
{
    protected static string $resource = AttendanceDayResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * The form holds wall-clock times in the employee's own zone; the row stores UTC.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var AttendanceDay $day */
        $day = $this->getRecord();
        $zone = $day->employee->time_zone ?: config('app.timezone');

        $data['date'] = $day->date->format('Y-m-d');
        $data['first_in'] = $day->first_in?->copy()->setTimezone($zone)->format('Y-m-d H:i:s');
        $data['last_out'] = $day->last_out?->copy()->setTimezone($zone)->format('Y-m-d H:i:s');

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return app(ManualDayEntry::class)->update($record, $data, Auth::user());
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(
                collect($exception->errors())
                    ->mapWithKeys(fn (array $messages, string $key): array => ['data.'.$key => $messages])
                    ->all(),
            );
        }
    }
}
