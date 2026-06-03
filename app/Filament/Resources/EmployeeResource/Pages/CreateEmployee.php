<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Services\Hr\EmployeeDepartmentService;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\CreateEmployee as BaseCreateEmployee;

class CreateEmployee extends BaseCreateEmployee
{
    protected function afterCreate(): void
    {
        $this->syncDepartmentsFromForm();
        $this->syncProfileExtensionFields();

        parent::afterCreate();
    }

    protected function syncProfileExtensionFields(): void
    {
        if (! Schema::hasColumn('employees_employees', 'primary_job_responsibilities')) {
            return;
        }

        $state = $this->form->getState();

        if (array_key_exists('primary_job_responsibilities', $state)) {
            $this->record->forceFill([
                'primary_job_responsibilities' => $state['primary_job_responsibilities'],
            ])->saveQuietly();
        }
    }

    protected function syncDepartmentsFromForm(): void
    {
        if (! Schema::hasTable('department_employee')) {
            return;
        }

        $state = $this->form->getState();
        $departments = $state['departments'] ?? [];

        if ($departments === []) {
            return;
        }

        app(EmployeeDepartmentService::class)->syncDepartments(
            $this->record,
            $departments,
            $state['department_id'] ?? null,
        );
    }
}
