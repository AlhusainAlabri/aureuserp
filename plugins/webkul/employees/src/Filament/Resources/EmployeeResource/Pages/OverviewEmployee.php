<?php

namespace Webkul\Employee\Filament\Resources\EmployeeResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Webkul\Employee\Filament\Resources\EmployeeResource;
use Webkul\Employee\Models\EmployeeDocument;
use Webkul\Employee\Models\EmployeeWarning;
use Webkul\Support\Traits\HasRecordNavigationTabs;

class OverviewEmployee extends Page
{
    use HasRecordNavigationTabs;
    use InteractsWithRecord {
        HasRecordNavigationTabs::getSubNavigation insteadof InteractsWithRecord;
    }

    protected static string $resource = EmployeeResource::class;

    protected string $view = 'employees::filament.resources.employee.pages.overview-employee';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->getRecord()->load([
            'documents',
            'warnings.warningType',
            'department',
            'job',
            'parent',
            'employmentType',
        ]);
    }

    public static function getNavigationLabel(): string
    {
        return __('employees::filament/resources/employee/pages/overview-employee.navigation.title');
    }

    public function getTitle(): string
    {
        return __('employees::filament/resources/employee/pages/overview-employee.title');
    }

    public function getExpiredDocuments(): Collection
    {
        return $this->getRecord()->documents->filter(fn (EmployeeDocument $doc): bool => $doc->isExpired());
    }

    public function getExpiringSoonDocuments(): Collection
    {
        return $this->getRecord()->documents->filter(fn (EmployeeDocument $doc): bool => $doc->isExpiringSoon());
    }

    public function getAlertDocuments(): Collection
    {
        return $this->getRecord()->documents
            ->filter(fn (EmployeeDocument $doc): bool => $doc->isExpired() || $doc->isExpiringSoon())
            ->sortByDesc(fn (EmployeeDocument $doc): bool => $doc->isExpired());
    }

    public function getActiveWarnings(): Collection
    {
        return $this->getRecord()->warnings->filter(
            fn (EmployeeWarning $warning): bool => ! $warning->is_acknowledged,
        );
    }

    public function getComplianceAlerts(): array
    {
        $employee = $this->getRecord();

        $items = [];

        $fields = [
            'visa_expire'                 => __('employees::filament/resources/employee/pages/overview-employee.compliance.visa-expire'),
            'work_permit_expiration_date' => __('employees::filament/resources/employee/pages/overview-employee.compliance.work-permit'),
            'civil_id_expiry'             => __('employees::filament/resources/employee/pages/overview-employee.compliance.civil-id'),
        ];

        foreach ($fields as $field => $label) {
            $date = $employee->{$field} ? Carbon::parse($employee->{$field}) : null;

            if ($date === null) {
                continue;
            }

            $items[] = [
                'label'  => $label,
                'date'   => $date,
                'color'  => $this->getExpiryColor($date),
                'status' => $this->getExpiryStatus($date),
            ];
        }

        return $items;
    }

    public function hasAnyAlerts(): bool
    {
        return $this->getAlertDocuments()->isNotEmpty()
            || collect($this->getComplianceAlerts())->isNotEmpty()
            || $this->getActiveWarnings()->isNotEmpty();
    }

    public function getDocumentTypeLabel(string $type): string
    {
        return match ($type) {
            'id_card'          => __('employees::filament/resources/employee.relation-manager/documents.form.fields.id-card'),
            'passport'         => __('employees::filament/resources/employee.relation-manager/documents.form.fields.passport'),
            'residence_permit' => __('employees::filament/resources/employee.relation-manager/documents.form.fields.residence-permit'),
            'contract'         => __('employees::filament/resources/employee.relation-manager/documents.form.fields.contract'),
            'certificate'      => __('employees::filament/resources/employee.relation-manager/documents.form.fields.certificate'),
            default            => __('employees::filament/resources/employee.relation-manager/documents.form.fields.other'),
        };
    }

    public function getDocumentTypeColor(string $type): string
    {
        return match ($type) {
            'id_card'          => 'info',
            'passport'         => 'purple',
            'contract'         => 'teal',
            'certificate'      => 'success',
            'residence_permit' => 'warning',
            default            => 'gray',
        };
    }

    private function getExpiryColor(Carbon $date): string
    {
        if ($date->isPast()) {
            return 'danger';
        }

        $days = (int) now()->diffInDays($date, false);

        if ($days <= 7) {
            return 'danger';
        }

        if ($days <= 30) {
            return 'warning';
        }

        return 'success';
    }

    private function getExpiryStatus(Carbon $date): string
    {
        if ($date->isPast()) {
            return __('employees::filament/resources/employee/pages/overview-employee.status.expired');
        }

        $days = (int) now()->diffInDays($date, false);

        if ($days <= 7) {
            return __('employees::filament/resources/employee/pages/overview-employee.status.expires-in-days', ['days' => $days]);
        }

        if ($days <= 30) {
            return __('employees::filament/resources/employee/pages/overview-employee.status.expires-in-days', ['days' => $days]);
        }

        return __('employees::filament/resources/employee/pages/overview-employee.status.valid');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editEmployee')
                ->label(__('employees::filament/resources/employee/pages/overview-employee.header-actions.edit'))
                ->icon('heroicon-o-pencil-square')
                ->url(fn (): string => EmployeeResource::getUrl('edit', ['record' => $this->getRecord()])),
            Action::make('addDocument')
                ->label(__('employees::filament/resources/employee/pages/overview-employee.header-actions.add-document'))
                ->icon('heroicon-o-document-plus')
                ->color('gray')
                ->url(fn (): string => EmployeeResource::getUrl('documents', ['record' => $this->getRecord()])),
            Action::make('issueWarning')
                ->label(__('employees::filament/resources/employee/pages/overview-employee.header-actions.issue-warning'))
                ->icon('heroicon-o-exclamation-triangle')
                ->color('warning')
                ->url(fn (): string => EmployeeResource::getUrl('warnings', ['record' => $this->getRecord()])),
        ];
    }
}
