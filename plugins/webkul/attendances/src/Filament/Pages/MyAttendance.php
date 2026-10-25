<?php

namespace Webkul\Attendance\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Webkul\Attendance\Enums\PunchDirection;
use Webkul\Attendance\Exceptions\AttendanceException;
use Webkul\Attendance\Models\AttendanceDay;
use Webkul\Attendance\Models\AttendancePunch;
use Webkul\Attendance\Services\SelfCheckInService;
use Webkul\Attendance\Settings\AttendanceSettings;
use Webkul\Employee\Models\Employee;

class MyAttendance extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-finger-print';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'attendances/my-attendance';

    protected string $view = 'attendances::filament.pages.my-attendance';

    public static function getNavigationLabel(): string
    {
        return __('attendances::attendances.my_attendance.navigation');
    }

    public static function getNavigationGroup(): string
    {
        return __('attendances::attendances.navigation.group');
    }

    public function getTitle(): string
    {
        return __('attendances::attendances.my_attendance.title');
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Employee::query()->where('user_id', Auth::id())->exists();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    #[Computed]
    public function employee(): ?Employee
    {
        return Employee::query()->where('user_id', Auth::id())->first();
    }

    /**
     * Whether the browser must send its position with the punch (a geofence is configured).
     */
    #[Computed]
    public function requiresLocation(): bool
    {
        return ! empty(app(AttendanceSettings::class)->geofence);
    }

    #[Computed]
    public function nextDirection(): PunchDirection
    {
        return app(SelfCheckInService::class)->nextDirection($this->employee);
    }

    /**
     * Today's punches of the employee, oldest first.
     *
     * @return Collection<int, AttendancePunch>
     */
    #[Computed]
    public function todayPunches(): Collection
    {
        $localNow = $this->localNow();

        return AttendancePunch::query()
            ->where('employee_id', $this->employee->id)
            ->where('punched_at', '>=', $localNow->copy()->startOfDay()->utc()->format('Y-m-d H:i:s'))
            ->where('punched_at', '<', $localNow->copy()->addDay()->startOfDay()->utc()->format('Y-m-d H:i:s'))
            ->orderBy('punched_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * The employee's own attendance days of the current local month, newest first.
     *
     * @return Collection<int, AttendanceDay>
     */
    #[Computed]
    public function monthDays(): Collection
    {
        $localNow = $this->localNow();

        return AttendanceDay::query()
            ->where('employee_id', $this->employee->id)
            ->whereDate('date', '>=', $localNow->copy()->startOfMonth()->toDateString())
            ->whereDate('date', '<=', $localNow->copy()->endOfMonth()->toDateString())
            ->orderByDesc('date')
            ->get();
    }

    public function checkInOrOut(?float $latitude = null, ?float $longitude = null): void
    {
        $employee = $this->employee;

        abort_unless($employee instanceof Employee, 403);

        try {
            $punch = app(SelfCheckInService::class)->punch($employee, request()->ip(), $latitude, $longitude);
        } catch (AttendanceException $exception) {
            Notification::make()
                ->danger()
                ->title($exception->getMessage())
                ->send();

            return;
        }

        unset($this->nextDirection, $this->todayPunches, $this->monthDays);

        Notification::make()
            ->success()
            ->title(__('attendances::attendances.my_attendance.'.($punch->direction === PunchDirection::Out ? 'recorded_out' : 'recorded_in')))
            ->body(__('attendances::attendances.my_attendance.recorded_at', ['time' => $this->localTime($punch->punched_at)]))
            ->send();
    }

    public function localTime(?Carbon $moment): string
    {
        return $moment?->copy()->setTimezone($this->zone())->format('H:i') ?? '-';
    }

    public function formatMinutes(int $minutes): string
    {
        return sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    protected function zone(): string
    {
        return $this->employee->time_zone ?: config('app.timezone');
    }

    protected function localNow(): Carbon
    {
        return Carbon::now($this->zone());
    }
}
