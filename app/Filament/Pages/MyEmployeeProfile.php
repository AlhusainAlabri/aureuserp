<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema as DbSchema;
use Webkul\Employee\Models\Employee;

class MyEmployeeProfile extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?int $navigationSort = 9;

    protected string $view = 'filament.pages.my-employee-profile';

    public ?Employee $employee = null;

    public function mount(): void
    {
        $this->employee = Employee::query()
            ->with(['department', 'departments'])
            ->where('user_id', Auth::id())
            ->first();
    }

    public static function getNavigationLabel(): string
    {
        return __('hr-extensions::profile.navigation');
    }

    public function getTitle(): string
    {
        return static::getNavigationLabel();
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Employee::query()->where('user_id', Auth::id())->exists();
    }

    public function hasDepartmentsPivot(): bool
    {
        return DbSchema::hasTable('department_employee');
    }

    public function hasResponsibilitiesField(): bool
    {
        return DbSchema::hasColumn('employees_employees', 'primary_job_responsibilities');
    }
}
