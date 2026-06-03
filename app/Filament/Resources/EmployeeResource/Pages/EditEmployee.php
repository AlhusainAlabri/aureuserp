<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Services\Hr\EmployeeDepartmentService;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\EditEmployee as BaseEditEmployee;

class EditEmployee extends BaseEditEmployee
{
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = parent::mutateFormDataBeforeFill($data);

        if (Schema::hasTable('department_employee')) {
            $data['departments'] = $this->record->departments->pluck('id')->all();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->syncDepartmentsFromForm();
        $this->syncProfileExtensionFields();

        parent::afterSave();
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
