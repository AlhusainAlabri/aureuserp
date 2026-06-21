<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Concerns\FiltersEmployeeFormDataForDatabaseSchema;
use App\Services\Hr\EmployeeDepartmentService;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\CreateEmployee as BaseCreateEmployee;

class CreateEmployee extends BaseCreateEmployee
{
    use FiltersEmployeeFormDataForDatabaseSchema;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);

        unset($data['departments']);

        if (array_key_exists('department_id', $data) && filled($data['department_id'])) {
            $data['department_id'] = (int) $data['department_id'];
        }

        return $this->filterEmployeeFormDataForDatabaseSchema($data);
    }

    protected function afterCreate(): void
    {
        $this->syncDepartmentsFromForm();
        $this->syncProfileExtensionFields();
    }

    protected function syncProfileExtensionFields(): void
    {
        if (! Schema::hasColumn('employees_employees', 'primary_job_responsibilities')) {
            return;
        }

        $state = $this->form->getRawState();

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

        $state = $this->form->getRawState();
        $departments = $state['departments'] ?? [];

        if ($departments === []) {
            return;
        }

        app(EmployeeDepartmentService::class)->syncDepartments(
            $this->record,
            $departments,
            $state['department_id'] ?? $this->record->department_id,
        );
    }
}
