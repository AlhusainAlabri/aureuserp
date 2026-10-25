<?php

namespace Webkul\Attendance\Filament\Resources\AttendanceDayResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Webkul\Attendance\Filament\Resources\AttendanceDayResource;
use Webkul\Attendance\Services\ManualDayEntry;

class CreateAttendanceDay extends CreateRecord
{
    protected static string $resource = AttendanceDayResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(ManualDayEntry::class)->create($data, Auth::user());
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(
                collect($exception->errors())
                    ->mapWithKeys(fn (array $messages, string $key): array => ['data.'.$key => $messages])
                    ->all(),
            );
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
