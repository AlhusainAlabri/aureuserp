<?php

namespace App\Providers;

use App\Listeners\Hr\NotifyLeaveSubstitute;
use App\Models\Hr\EmployeeContract;
use App\Models\Hr\EmployeeSalaryRaise;
use App\Models\Hr\EmployeeSelfAssessment;
use App\Models\Hr\EmployeeTraining;
use App\Observers\EmployeeDepartmentObserver;
use App\Services\Hr\HrExtensionSchemaService;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Webkul\Employee\Models\Department;
use Webkul\Employee\Models\Employee;
use Webkul\Employee\Models\EmployeeSubmission;
use Webkul\Payroll\Models\Payslip;
use Webkul\Security\Models\User;
use Webkul\TimeOff\Models\Leave;

class HrExtensionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(config_path('hr-extensions.php'), 'hr-extensions');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(lang_path('hr-extensions'), 'hr-extensions');
        $this->loadTranslationsFrom(lang_path('purchases-extensions'), 'purchases-extensions');

        FileUpload::configureUsing(function (FileUpload $component): void {
            if (filled($component->getPlaceholder())) {
                return;
            }

            $component->placeholder(__('hr-extensions::employee.file_upload_placeholder'));
        });

        if (! class_exists(Employee::class)) {
            return;
        }

        app(HrExtensionSchemaService::class)->ensure();

        $this->registerEmployeeRelations();
        Employee::observe(EmployeeDepartmentObserver::class);
        $this->registerHrPermissions();

        if (class_exists(Leave::class)) {
            Leave::resolveRelationUsing('substituteEmployee', fn (Leave $leave) => $leave->belongsTo(Employee::class, 'substitute_employee_id'));
            Event::listen('eloquent.created: '.Leave::class, [NotifyLeaveSubstitute::class, 'handleCreated']);
        }

        if (class_exists(EmployeeSubmission::class)) {
            EmployeeSubmission::created(function (EmployeeSubmission $submission): void {
                if ($submission->is_anonymous) {
                    $submission->forceFill([
                        'submitter_name' => __('hr-extensions::submissions.anonymous_label'),
                    ])->saveQuietly();
                }
            });
        }
    }

    protected function registerEmployeeRelations(): void
    {
        Employee::resolveRelationUsing('departments', function (Employee $employee) {
            return $employee->belongsToMany(
                Department::class,
                'department_employee',
                'employee_id',
                'department_id',
            )->withPivot(['is_primary', 'start_date', 'end_date'])->withTimestamps();
        });

        Employee::resolveRelationUsing('trainings', fn (Employee $employee) => $employee->hasMany(EmployeeTraining::class, 'employee_id'));
        Employee::resolveRelationUsing('salaryRaises', fn (Employee $employee) => $employee->hasMany(EmployeeSalaryRaise::class, 'employee_id'));
        Employee::resolveRelationUsing('contracts', fn (Employee $employee) => $employee->hasMany(EmployeeContract::class, 'employee_id'));
        Employee::resolveRelationUsing('selfAssessments', fn (Employee $employee) => $employee->hasMany(EmployeeSelfAssessment::class, 'employee_id'));
        Employee::resolveRelationUsing('closedBy', fn (Employee $employee) => $employee->belongsTo(User::class, 'closed_by'));
        Employee::resolveRelationUsing('reopenedBy', fn (Employee $employee) => $employee->belongsTo(User::class, 'reopened_by'));

        if (class_exists(Payslip::class) && Schema::hasTable('payroll_payslips')) {
            Employee::resolveRelationUsing('payslips', fn (Employee $employee) => $employee->hasMany(Payslip::class, 'employee_id'));
        }
    }

    public function registerHrPermissions(): void
    {
        if (! class_exists(Role::class)) {
            return;
        }

        $permissions = [
            'close_employee_file',
            'reopen_employee_file',
            'review_self_assessment',
            'view_any_self_assessment',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'web',
            ]);
        }

        foreach (['Admin', 'hr_manager', 'super_admin'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->first();

            if ($role) {
                $role->givePermissionTo(['close_employee_file', 'reopen_employee_file', 'review_self_assessment', 'view_any_self_assessment']);
            }
        }

        foreach (['manager', 'department_manager'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->first();

            if ($role) {
                $role->givePermissionTo(['review_self_assessment']);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
