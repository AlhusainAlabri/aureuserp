<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Concerns\FiltersEmployeeFormDataForDatabaseSchema;
use App\Services\Hr\EmployeeDepartmentService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Schema;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Employee\Filament\Resources\EmployeeResource;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\EditEmployee as BaseEditEmployee;
use Webkul\Support\Models\ActivityPlan;

class EditEmployee extends BaseEditEmployee
{
    use FiltersEmployeeFormDataForDatabaseSchema;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = parent::mutateFormDataBeforeSave($data);

        unset($data['departments']);

        if (array_key_exists('department_id', $data) && filled($data['department_id'])) {
            $data['department_id'] = (int) $data['department_id'];
        }

        return $this->filterEmployeeFormDataForDatabaseSchema($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->label(__('employees::filament/resources/employee/pages/edit-employee.header-actions.chatter'))
                ->resource(EmployeeResource::class)
                ->activityPlans(ActivityPlan::employees()->pluck('name', 'id')),
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('employees::filament/resources/employee/pages/edit-employee.header-actions.delete.notification.title'))
                        ->body(__('employees::filament/resources/employee/pages/edit-employee.header-actions.delete.notification.body')),
                ),
        ];
    }

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
